<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// ─────────────────────────────────────────────────────────────────────────────
// Sent TO: Both customer AND vendor
// When:    Payment is successfully confirmed via Razorpay
//
// For direct pay:      status = processing, payment_status = paid
// For pay-later pay:  status = delivered,  payment_status = paid
// ─────────────────────────────────────────────────────────────────────────────
class PaymentConfirmedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public OrderItem $item) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload($notifiable);
    }

    public function toBroadcast($notifiable): array
    {
        return ['data' => $this->payload($notifiable)];
    }

    public function broadcastOn(): array
    {
        // Broadcast to both customer and vendor channels
        return [
            new PrivateChannel('customer.' . $this->item->order->customer_id),
            new PrivateChannel('vendor.' . $this->item->vendor_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    private function payload($notifiable): array
    {
        // Determine if the recipient is the vendor or the customer
        $isVendor       = $notifiable->id === $this->item->vendor_id;
        $recipientType  = $isVendor ? 'vendor' : 'customer';

        $title = '💰 Payment Confirmed';
        $body  = $isVendor
            ? "Payment of ₹{$this->item->total_amount} received for order #{$this->item->order_id}."
            : "Your payment of ₹{$this->item->total_amount} was successful!";

        return [
            // ── Notification display ──────────────────────────────────────────
            'title'          => $title,
            'body'           => $body,

            // ── Status after payment ──────────────────────────────────────────
            'status'         => $this->item->status,          // processing or delivered
            'payment_status' => $this->item->payment_status,  // paid

            // ── Order & payment data ──────────────────────────────────────────
            'order_item_id'  => $this->item->id,
            'order_id'       => $this->item->order_id,
            'total_amount'   => (float) $this->item->total_amount,
            'subtotal'       => (float) $this->item->subtotal,
            'delivery_charge' => (float) ($this->item->delivery_charge ?? 0),
            'paid_at'        => $this->item->paid_at?->toDateTimeString(),

            // ── Item details ──────────────────────────────────────────────────
            'product_name'   => $this->item->product->name ?? '',
            'quantity_unit'  => $this->item->quantity_unit,
            'vendor_name'    => $this->item->vendor->name ?? '',
            'customer_name'  => $this->item->order->customer->name ?? '',

            // ── Routing ───────────────────────────────────────────────────────
            'recipient_type' => $recipientType,
        ];
    }
}
