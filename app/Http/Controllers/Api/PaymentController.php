<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/orders/{orderItemId}/pay-now
    // ─────────────────────────────────────────────────────────────────────────
    public function payNow(Request $request, int $orderItemId)
    {
        try {
            $item = $this->findCustomerItem($request->user()->id, $orderItemId);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // ── Guards ────────────────────────────────────────────────────────
            if ($item->status !== OrderItem::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only accepted orders can be paid. Current status: ' . $item->status,
                ], 422);
            }

            if ($item->payment_status === OrderItem::PAYMENT_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.',
                ], 422);
            }

            // ── TODO: add Razorpay / Stripe verification here ─────────────────
            // Example with Razorpay:
            // $request->validate(['razorpay_payment_id' => 'required|string']);
            // $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            // $api->payment->fetch($request->razorpay_payment_id)->capture(['amount' => $item->total_amount * 100]);

            // ── Update payment + move order to processing ─────────────────────
            DB::transaction(function () use ($item) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_PAID,
                    'paid_at'        => now(),
                    'status'         => 'processing',
                    'actioned_at'    => now(),
                ]);

                // Recalculate parent order status
                $item->order->recalculateStatus();
            });

            // ── Notify customer of the status change ──────────────────────────
            $freshItem = $item->fresh(['order', 'product', 'listing', 'vendor']);
            $item->order->customer->notify(
                new OrderStatusUpdatedNotification($freshItem)
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your order is now being processed.',
                'data'    => [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'payment_status' => OrderItem::PAYMENT_PAID,
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
    // ─────────────────────────────────────────────────────────────────────────
    public function payLater(Request $request, int $orderItemId)
    {
        try {
            $item = $this->findCustomerItem($request->user()->id, $orderItemId);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // ── Guards ────────────────────────────────────────────────────────
            if ($item->status !== OrderItem::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only accepted orders can use pay later. Current status: ' . $item->status,
                ], 422);
            }

            if ($item->payment_status === OrderItem::PAYMENT_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.',
                ], 422);
            }

            if ($item->payment_status === OrderItem::PAYMENT_LATER) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pay later is already set. Due: ' . $item->payment_due_at?->format('d M Y'),
                ], 422);
            }

            // ── Set pay later with 3-day window ───────────────────────────────
            $dueAt = now()->addDays(3);

            DB::transaction(function () use ($item, $dueAt) {
                $item->update([
                    'payment_status' => OrderItem::PAYMENT_LATER,
                    'payment_due_at' => $dueAt,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Pay later confirmed. Payment due by ' . $dueAt->format('d M Y') . '.',
                'data'    => [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'payment_status' => OrderItem::PAYMENT_LATER,
                    'payment_due_at' => $dueAt->toDateTimeString(),
                    'total_due'      => $item->total_amount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set pay later. Please try again.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/orders/{orderItemId}/payment-status
    // Flutter polls this after returning from payment gateway
    // ─────────────────────────────────────────────────────────────────────────
    public function status(Request $request, int $orderItemId)
    {
        $item = $this->findCustomerItem($request->user()->id, $orderItemId);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'order_item_id'  => $item->id,
                'order_id'       => $item->order_id,
                'order_status'   => $item->status,
                'payment_status' => $item->payment_status ?? OrderItem::PAYMENT_UNPAID,
                'payment_due_at' => $item->payment_due_at?->toDateTimeString(),
                'paid_at'        => $item->paid_at?->toDateTimeString(),
                'subtotal'       => (float) $item->subtotal,
                'delivery_charge' => (float) ($item->delivery_charge ?? 0),
                'total_amount'   => $item->total_amount,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helper — finds an order item that belongs to the given customer
    // Uses order->customer_id to verify ownership (matches your Order model)
    // ─────────────────────────────────────────────────────────────────────────
    private function findCustomerItem(int $customerId, int $orderItemId): ?OrderItem
    {
        return OrderItem::with(['order', 'product', 'listing', 'vendor'])
            ->whereHas('order', fn($q) => $q->where('customer_id', $customerId))
            ->find($orderItemId);
    }
}
