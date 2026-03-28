<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\OrderItem;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PayLaterRequestedNotification;
use App\Notifications\PayLaterDecisionNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // =========================================================================
    // FLOW OVERVIEW
    // =========================================================================
    // 1. Customer places order            → status = pending
    // 2. Vendor accepts order             → status = accepted, payment_status = unpaid
    // 3a. Customer pays now (Razorpay)    → status = processing, payment_status = paid
    //     → Both notified → Done
    // 3b. Customer requests pay later     → payment_status = pay_later (status stays = accepted)
    //     → Vendor notified to approve/reject
    // 4a. Vendor approves pay later       → status = processing, payment_status = pay_later
    //     → Customer notified: "Order approved, pay by [date]"
    // 4b. Vendor rejects pay later        → status = declined, payment_status = unpaid
    //     → Customer notified: "Pay later rejected, order cancelled"
    // 5.  Customer pays from notification → status = delivered, payment_status = paid
    //     → Both notified → Done
    // =========================================================================

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-now
    //
    // Customer pays immediately via Razorpay.
    // Can be called in two states:
    //   (a) status=accepted, payment_status=unpaid  → direct payment
    //   (b) status=processing, payment_status=pay_later → paying after vendor approved pay-later
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
            // Payment allowed when:
            //   - Order is accepted (direct pay) OR processing (after pay-later approval)
            $allowedStatuses = [OrderItem::STATUS_ACCEPTED, 'processing'];
            if (! in_array($item->status, $allowedStatuses)) {
                return $this->error(
                    'Payment not allowed for orders with status: ' . $item->status
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

            if (!in_array($payment->status, ['authorized', 'captured'])) {
                return $this->error('Payment failed. Status: ' . $payment->status);
            }

            // ── SECURITY: Verify currency ─────────────────────────────────────
            if ($payment->currency !== 'INR') {
                return $this->error('Invalid currency. Expected INR, got: ' . $payment->currency);
            }

            // ── SECURITY: Verify amount ───────────────────────────────────────
            // Razorpay stores amount in paise (1 INR = 100 paise).
            // Compare against order total_amount (subtotal + delivery_charge).
            // Allow ±1 rupee tolerance only to cover floating-point rounding.
            $numericTotal    = (float) $item->total_amount;
            $numericSubtotal = (float) $item->subtotal;
            $numericDelivery = (float) ($item->delivery_charge ?? 0);

            $expectedPaise = (int) round($numericTotal * 100);
            $paidPaise     = (int) $payment->amount;

            if (abs($expectedPaise - $paidPaise) > 100) {
                // Log for audit — someone is trying to underpay
                \Illuminate\Support\Facades\Log::warning('Razorpay amount mismatch', [
                    'order_item_id'  => $item->id,
                    'customer_id'    => $request->user()->id,
                    'expected_paise' => $expectedPaise,
                    'paid_paise'     => $paidPaise,
                    'payment_id'     => $request->razorpay_payment_id,
                ]);
                return $this->error(
                    'Payment amount mismatch. Expected ₹' . number_format($numericTotal, 2) .
                    ', received ₹' . number_format($paidPaise / 100, 2) . '. Please contact support.'
                );
            }

            // ── Update order ──────────────────────────────────────────────────
            // After payment:
            //   - If it was a direct pay (accepted → paid): status = processing
            //   - If it was a pay-later payment (processing + pay_later): status = delivered
            $wasPayLater = $item->payment_status === OrderItem::PAYMENT_LATER;
            $newStatus   = $wasPayLater ? 'delivered' : 'processing';

            DB::transaction(function () use ($item, $newStatus) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_PAID,
                    'paid_at'        => now(),
                    'status'         => $newStatus,
                    'actioned_at'    => now(),
                ]);
                // ✅ FIXED: Permanently disabled auto-decline bug
                // $item->order->recalculateStatus();
            });

            // ── Notify customer + vendor ──────────────────────────────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->order->customer->notify(new PaymentConfirmedNotification($fresh));
            $fresh->vendor->notify(new PaymentConfirmedNotification($fresh));

            // ── Deduct stock when entering 'processing' for the first time ────
            // wasPayLater=true means acceptPayLater() already deducted stock when
            // the order entered processing; don't deduct again here.
            if (! $wasPayLater) {
                $listing = MarketplaceListing::find($item->listing_id);
                if ($listing) {
                    $listing->decrement('available_stock_unit', $item->quantity_unit);
                    if ($listing->fresh()->available_stock_unit <= 0) {
                        $listing->update(['status' => MarketplaceListing::STATUS_INACTIVE]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your order is now being ' . ($wasPayLater ? 'delivered.' : 'processed.'),
                'data'    => [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'payment_status' => 'paid',
                    'order_status'   => $newStatus,
                    'paid_at'        => now()->toDateTimeString(),
                    'total_paid'     => $numericTotal,  // ✅ NUMERIC not string
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
    // Status stays = accepted. payment_status = pay_later.
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

            // ── Set pay later — status stays 'accepted', payment_status = pay_later ──
            DB::transaction(function () use ($item, $dueAt, $days) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_LATER,
                    'payment_due_at' => $dueAt,
                    'days_requested' => $days,
                    // status intentionally NOT changed — still 'accepted'
                    // Vendor must approve before order progresses
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
                    'order_status'          => $item->status, // still 'accepted'
                    'days_requested'        => $days,
                    'payment_due_at'        => $dueAt->toDateTimeString(),
                    'payment_due_formatted' => $dueAt->format('d M Y'),
                    'total_amount'          => (float) $item->total_amount,  // ✅ FIXED
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
    // Order moves to 'processing' (goods will be dispatched/delivered).
    // Payment is still pending — customer pays later by due date.
    // Customer notified: "Order is being processed, pay by [date]".
    // ─────────────────────────────────────────────────────────────────────────
    public function acceptPayLater(Request $request, int $orderItemId): JsonResponse
    {
        try {
            $item = $this->findVendorItem($request->user()->id, $orderItemId);
            if (! $item) return $this->notFound();

            if ($item->payment_status !== OrderItem::PAYMENT_LATER) {
                return $this->error('No pay later request found for this order.');
            }

            if ($item->status !== OrderItem::STATUS_ACCEPTED) {
                return $this->error(
                    'This order cannot be actioned. Current status: ' . $item->status
                );
            }

            // ── Move order to processing — payment still pending ──────────────
            DB::transaction(function () use ($item) {
                $item->update([
                    'status'      => 'processing',
                    'actioned_at' => now(),
                    // payment_status stays 'pay_later' — customer still needs to pay
                ]);
            });

            // ── Deduct stock now that the order is entering processing ─────────
            $listing = MarketplaceListing::find($item->listing_id);
            if ($listing) {
                $listing->decrement('available_stock_unit', $item->quantity_unit);
                if ($listing->fresh()->available_stock_unit <= 0) {
                    $listing->update(['status' => MarketplaceListing::STATUS_INACTIVE]);
                }
            }

            // ── Notify customer: order is processing, pay by date ─────────────
            $fresh = $item->fresh(['order.customer', 'product', 'vendor']);
            $fresh->order->customer->notify(
                new PayLaterDecisionNotification($fresh, accepted: true)
            );

            return response()->json([
                'success' => true,
                'message' => 'Pay later approved. Order is now processing.',
                'data'    => [
                    'order_item_id'         => $item->id,
                    'order_status'          => 'processing',
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
    // Customer must pay now or order is declined.
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
                    'payment_due_at'   => null,
                    'days_requested'   => null,
                    'rejection_reason' => $request->reason ?? 'Pay later not accepted.',
                    'actioned_at'      => now(),
                ]);
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
    // Flutter polls this to check current state.
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
                'total_amount'          => (float) $item->total_amount,  // ✅ CRITICAL FIX
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
