<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PayLaterRequestedNotification;
use App\Notifications\PayLaterDecisionNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-now
    //
    // Customer pays immediately via Razorpay.
    // Flow: Flutter opens Razorpay → gets payment_id → calls this endpoint
    //       → backend verifies with Razorpay → marks paid → notifies both.
    //
    // FIX SUMMARY:
    //   1. capture() call no longer passes 'currency' — only 'amount' is valid.
    //   2. Both 'captured' AND 'authorized' are accepted as success (test mode
    //      auto-captures so status may already be 'captured' before we call it).
    //   3. Detailed Log::error() so the real exception is always visible in
    //      storage/logs/laravel.log regardless of APP_DEBUG setting.
    // ─────────────────────────────────────────────────────────────────────────
    public function payNow(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $request->validate([
                'razorpay_payment_id' => 'required|string',
            ]);

            Log::info('PayNow endpoint HIT', [
                'order_item_id' => $orderItemId,
                'user_id' => $request->user()?->id ?? 'NO_AUTH',
                'payment_id' => $request->razorpay_payment_id,
            ]);

            $item = $this->findCustomerItem($request->user()->id, $orderItemId);
            Log::info('OrderItem lookup', [
                'order_item_id' => $orderItemId,
                'item_found' => $item ? 'YES status=' . $item->status . ' payment=' . ($item->payment_status ?? 'null') : 'NO',
            ]);

            if (! $item) return $this->notFound();

            Log::info('Razorpay config', [
                'key_exists' => !empty(config('services.razorpay.key')),
                'secret_exists' => !empty(config('services.razorpay.secret')),
                'key_prefix' => substr(config('services.razorpay.key', ''), 0, 10) . '...',
            ]);
            if (! $item) return $this->notFound();

            // ── Guards ────────────────────────────────────────────────────────
            if ($item->status !== OrderItem::STATUS_ACCEPTED) {
                return $this->error(
                    'Only accepted orders can be paid. Current status: ' . $item->status
                );
            }

            if ($item->payment_status === OrderItem::PAYMENT_PAID) {
                return $this->error('This order has already been paid.');
            }

            // ── Razorpay verification ─────────────────────────────────────────
            $api = new \Razorpay\Api\Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $payment = $api->payment->fetch($request->razorpay_payment_id);

            Log::info('Razorpay payment fetched', [
                'order_item_id' => $orderItemId,
                'payment_id'    => $request->razorpay_payment_id,
                'status'        => $payment->status,
                'amount'        => $payment->amount,
                'method'        => $payment->method ?? 'unknown',
            ]);

            // In TEST mode UPI/netbanking payments often land directly in
            // 'captured'. Only call capture() if still sitting at 'authorized'.
            //
            // FIX: pass ONLY 'amount' to capture() — passing 'currency' causes
            // the Razorpay PHP SDK to throw a BadRequestError, which was being
            // silently swallowed by the outer catch and returning "Payment failed"
            // to Flutter even though Razorpay had already processed the money.
            if ($payment->status === 'authorized') {
                Log::info('Capturing authorized payment', [
                    'payment_id' => $request->razorpay_payment_id,
                    'amount'     => $payment->amount,
                ]);

                $payment = $payment->capture(['amount' => $payment->amount]);

                Log::info('Capture result', [
                    'payment_id' => $request->razorpay_payment_id,
                    'status'     => $payment->status,
                ]);
            }

            // Accept 'captured' (normal) or 'authorized' (some test-mode flows
            // where auto-capture is enabled on the Razorpay dashboard).
            if (! in_array($payment->status, ['captured', 'authorized'])) {
                Log::warning('Payment not in acceptable status after capture attempt', [
                    'order_item_id' => $orderItemId,
                    'payment_id'    => $request->razorpay_payment_id,
                    'status'        => $payment->status,
                ]);

                return $this->error(
                    'Payment not completed on Razorpay. Status: ' . $payment->status
                );
            }

            // ── Update order ──────────────────────────────────────────────────
            DB::transaction(function () use ($item) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_PAID,
                    'paid_at'        => now(),
                    'status'         => 'processing',
                    'actioned_at'    => now(),
                ]);
                $item->order->recalculateStatus();
            });

            Log::info('Order marked as paid', [
                'order_item_id'  => $item->id,
                'payment_status' => 'paid',
                'order_status'   => 'processing',
            ]);

            // ── Notify customer + vendor ──────────────────────────────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->order->customer->notify(new PaymentConfirmedNotification($fresh));
            $fresh->vendor->notify(new PaymentConfirmedNotification($fresh));

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your order is now being processed.',
                'data'    => [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'payment_status' => 'paid',
                    'order_status'   => 'processing',
                    'paid_at'        => now()->toDateTimeString(),
                    'total_paid'     => $item->total_amount,
                ],
            ]);
        } catch (\Exception $e) {
            // Always log the full exception so the real cause is visible in
            // storage/logs/laravel.log — not just when APP_DEBUG is true.
            Log::error('PayNow failed', [
                'order_item_id' => $orderItemId,
                'payment_id'    => $request->razorpay_payment_id ?? 'none',
                'error'         => $e->getMessage(),
                'class'         => get_class($e),
                'trace'         => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please contact support if amount was deducted.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-later
    //
    // Customer requests pay later with custom days (1–7).
    // Vendor gets a notification to approve or reject.
    // Body: { "days_requested": 3 }
    // ─────────────────────────────────────────────────────────────────────────
    public function payLater(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $request->validate([
                'days_requested' => 'required|integer|min:1|max:7',
            ]);

            $item = $this->findCustomerItem($request->user()->id, $orderItemId);
            if (! $item) return $this->notFound();

            // ── Guards ────────────────────────────────────────────────────────
            if ($item->status !== OrderItem::STATUS_ACCEPTED) {
                return $this->error(
                    'Only accepted orders can use pay later. Current status: ' . $item->status
                );
            }

            if ($item->payment_status === OrderItem::PAYMENT_PAID) {
                return $this->error('This order has already been paid.');
            }

            if ($item->payment_status === OrderItem::PAYMENT_LATER) {
                return $this->error(
                    'Pay later already requested. Due: ' . $item->payment_due_at?->format('d M Y')
                );
            }

            $days  = (int) $request->days_requested;
            $dueAt = now()->addDays($days);

            // ── Set pay later ─────────────────────────────────────────────────
            DB::transaction(function () use ($item, $dueAt, $days) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_LATER,
                    'payment_due_at' => $dueAt,
                    'days_requested' => $days,
                ]);
            });

            // ── Notify vendor to approve/reject ───────────────────────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->vendor->notify(new PayLaterRequestedNotification($fresh, $fresh->vendor));

            return response()->json([
                'success' => true,
                'message' => "Pay later requested for {$days} day(s). Waiting for vendor approval.",
                'data'    => [
                    'order_item_id'         => $item->id,
                    'order_id'              => $item->order_id,
                    'payment_status'        => 'pay_later',
                    'days_requested'        => $days,
                    'payment_due_at'        => $dueAt->toDateTimeString(),
                    'payment_due_formatted' => $dueAt->format('d M Y'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PayLater failed', [
                'order_item_id' => $orderItemId,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set pay later.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-later/accept  — VENDOR
    //
    // Vendor approves pay later.
    // Order is marked DELIVERED (complete). Payment just comes later.
    // Customer notified: "Order complete, pay by [date]".
    // ─────────────────────────────────────────────────────────────────────────
    public function acceptPayLater(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $item = $this->findVendorItem($request->user()->id, $orderItemId);
            if (! $item) return $this->notFound();

            if ($item->payment_status !== OrderItem::PAYMENT_LATER) {
                return $this->error('No pay later request found for this order.');
            }

            // ── Mark order as COMPLETE (delivered) ────────────────────────────
            DB::transaction(function () use ($item) {
                $item->update([
                    'status'      => 'delivered',
                    'actioned_at' => now(),
                ]);
                $item->order->recalculateStatus();
            });

            // ── Notify customer: order complete, payment pending ───────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->order->customer->notify(
                new PayLaterDecisionNotification($fresh, accepted: true)
            );

            return response()->json([
                'success' => true,
                'message' => 'Pay later approved. Order marked as complete.',
                'data'    => [
                    'order_item_id'         => $item->id,
                    'order_status'          => 'delivered',
                    'payment_status'        => 'pay_later',
                    'payment_due_at'        => $item->payment_due_at?->toDateTimeString(),
                    'payment_due_formatted' => $item->payment_due_at?->format('d M Y'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AcceptPayLater failed', [
                'order_item_id' => $orderItemId,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-later/reject  — VENDOR
    //
    // Vendor rejects pay later. Order is cancelled.
    // Body: { "reason": "Cannot offer credit at this time." }
    // ─────────────────────────────────────────────────────────────────────────
    public function rejectPayLater(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $item = $this->findVendorItem($request->user()->id, $orderItemId);
            if (! $item) return $this->notFound();

            if ($item->payment_status !== OrderItem::PAYMENT_LATER) {
                return $this->error('No pay later request found for this order.');
            }

            // ── Cancel the order ──────────────────────────────────────────────
            DB::transaction(function () use ($item, $request) {
                $item->update([
                    'status'           => OrderItem::STATUS_DECLINED,
                    'payment_status'   => OrderItem::PAYMENT_UNPAID,
                    'rejection_reason' => $request->reason ?? 'Pay later not accepted.',
                    'actioned_at'      => now(),
                ]);
                $item->order->recalculateStatus();
            });

            // ── Notify customer: rejected ─────────────────────────────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->order->customer->notify(
                new PayLaterDecisionNotification(
                    $fresh,
                    accepted: false,
                    reason: $request->reason
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Pay later rejected. Order has been cancelled.',
            ]);
        } catch (\Exception $e) {
            Log::error('RejectPayLater failed', [
                'order_item_id' => $orderItemId,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/orders/{orderItemId}/payment-status
    //
    // Flutter polls this after returning from payment gateway.
    // ─────────────────────────────────────────────────────────────────────────
    public function status(Request $request, int $orderItemId): JsonResponse
    {
        $item = $this->findCustomerItem($request->user()->id, $orderItemId);
        if (! $item) return $this->notFound();

        $dueAt     = $item->payment_due_at;
        $isOverdue = $dueAt
            && now()->isAfter($dueAt)
            && $item->payment_status === OrderItem::PAYMENT_LATER;

        return response()->json([
            'success' => true,
            'data'    => [
                'order_item_id'         => $item->id,
                'order_id'              => $item->order_id,
                'order_status'          => $item->status,
                'payment_status'        => $item->payment_status ?? OrderItem::PAYMENT_UNPAID,
                'days_requested'        => $item->days_requested,
                'payment_due_at'        => $dueAt?->toDateTimeString(),
                'payment_due_formatted' => $dueAt?->format('d M Y'),
                'is_overdue'            => $isOverdue,
                'paid_at'               => $item->paid_at?->toDateTimeString(),
                'subtotal'              => (float) $item->subtotal,
                'delivery_charge'       => (float) ($item->delivery_charge ?? 0),
                'total_amount'          => $item->total_amount,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function findCustomerItem(int $customerId, int $orderItemId): ?OrderItem
    {
        return OrderItem::with(['order.customer', 'product', 'vendor'])
            ->whereHas('order', fn($q) => $q->where('customer_id', $customerId))
            ->find($orderItemId);
    }

    private function findVendorItem(int $vendorId, int $orderItemId): ?OrderItem
    {
        return OrderItem::with(['order.customer', 'product', 'vendor'])
            ->where('vendor_id', $vendorId)
            ->find($orderItemId);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
    }

    private function error(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
