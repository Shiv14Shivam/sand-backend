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

class PaymentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-now
    //
    // Customer pays immediately via Razorpay.
    // Flow: Flutter opens Razorpay → gets payment_id → calls this endpoint
    //       → backend verifies with Razorpay → marks paid → notifies both.
    // ─────────────────────────────────────────────────────────────────────────
    public function payNow(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $request->validate([
                'razorpay_payment_id' => 'required|string',
            ]);

            $item = $this->findCustomerItem($request->user()->id, $orderItemId);
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

            if ($payment->status !== 'captured') {
                return $this->error('Payment not completed on Razorpay. Status: ' . $payment->status);
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
            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again.',
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
            $fresh->vendor->notify(new PayLaterRequestedNotification($fresh));

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
                    'status'      => 'delivered',   // ← Order is DONE
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
