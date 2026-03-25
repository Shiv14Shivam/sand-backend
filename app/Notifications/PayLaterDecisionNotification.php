<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

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
        // Customer hears back about vendor's decision
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
            // ── Accepted: order is COMPLETE, payment just comes later ──────────
            'title'  => $this->accepted
                ? '🎉 Order Complete!'
                : '❌ Pay Later Rejected',

            'body'   => $this->accepted
                ? "Your order is complete! Please pay ₹{$this->item->total_amount} by {$due}."
                : "Vendor rejected pay later. Reason: " . ($this->reason ?? 'Not specified') . '. Order cancelled.',

            // ── Order status = delivered means complete ────────────────────────
            'status'                => $this->accepted ? 'delivered' : 'declined',
            'payment_status'        => $this->accepted ? 'pay_later' : 'unpaid',

            'order_item_id'         => $this->item->id,
            'order_id'              => $this->item->order_id,
            'total_amount'          => $this->item->total_amount,
            'days_requested'        => $this->item->days_requested,
            'payment_due_at'        => $this->item->payment_due_at?->toDateTimeString(),
            'payment_due_formatted' => $due,
            'rejection_reason'      => $this->reason,
            'product_name'          => $this->item->product->name ?? '',
            'vendor_name'           => $this->item->vendor->name ?? '',
        ];
    }
}
