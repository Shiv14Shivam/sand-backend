<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// ─────────────────────────────────────────────────────────────────────────────
// Sent TO: Customer
// When:    Vendor approves OR rejects the customer's pay later request
//
// If accepted:
//   - order status = 'processing' (vendor is preparing/delivering)
//   - payment_status = 'pay_later' (customer still needs to pay)
//   - Flutter shows: Pay Now button + due date banner
//
// If rejected:
//   - order status = 'declined'
//   - payment_status = 'unpaid'
//   - Flutter shows: status banner only (order cancelled)
// ─────────────────────────────────────────────────────────────────────────────
class PayLaterDecisionNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public OrderItem $item,
        public bool      $accepted,
        public ?string   $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast($notifiable): array
    {
        return ['data' => $this->payload()];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->item->order->customer_id)];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    private function payload(): array
    {
        $due = $this->item->payment_due_at?->format('d M Y');

        return [
            // ── Notification display ──────────────────────────────────────────
            'title'  => $this->accepted
                ? '✅ Pay Later Approved!'
                : '❌ Pay Later Rejected',

            'body'   => $this->accepted
                ? "Your order is being processed! Please pay ₹{$this->item->total_amount} by {$due}."
                : 'Your pay later request was rejected. Order has been cancelled. Reason: ' . ($this->reason ?? 'Not specified'),

            // ── CRITICAL: correct statuses ────────────────────────────────────
            // Accepted: order moves to 'processing', payment still pending (pay_later)
            // Rejected: order cancelled (declined), payment cleared (unpaid)
            'status'                => $this->accepted ? 'processing' : 'declined',
            'payment_status'        => $this->accepted ? OrderItem::PAYMENT_LATER : OrderItem::PAYMENT_UNPAID,

            // ── Order & payment data ──────────────────────────────────────────
            'order_item_id'         => $this->item->id,
            'order_id'              => $this->item->order_id,
            'total_amount'          => (float) $this->item->total_amount,
            'subtotal'              => (float) $this->item->subtotal,
            'delivery_charge'       => (float) ($this->item->delivery_charge ?? 0),
            'days_requested'        => $this->item->days_requested,
            'payment_due_at'        => $this->item->payment_due_at?->toDateTimeString(),
            'payment_due_formatted' => $due,
            'rejection_reason'      => $this->reason,

            // ── Item details ──────────────────────────────────────────────────
            'product_name'          => $this->item->product->name ?? '',
            'vendor_name'           => $this->item->vendor->name ?? '',
            'quantity_unit'         => $this->item->quantity_unit,

            // ── Routing: Flutter uses this to know it's a customer notification ─
            'recipient_type'        => 'customer',
        ];
    }
}
