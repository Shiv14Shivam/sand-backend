<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// ─────────────────────────────────────────────────────────────────────────────
// Sent TO: Vendor
// When:    Customer requests pay later on an accepted order
// Vendor action: Approve or Reject
// ─────────────────────────────────────────────────────────────────────────────
class PayLaterRequestedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public OrderItem $item) {}

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
        return [new PrivateChannel('vendor.' . $this->item->vendor_id)];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    private function payload(): array
    {
        $days = $this->item->days_requested ?? 3;
        $due  = $this->item->payment_due_at?->format('d M Y');

        return [
            // ── Notification display ──────────────────────────────────────────
            'title'  => '⏰ Pay Later Requested',
            'body'   => "Customer wants {$days} day(s) to pay ₹{$this->item->total_amount}. Due: {$due}. Please approve or reject.",

            // ── THIS is the key field Flutter uses to decide which buttons to show
            // 'pay_later_pending' tells the vendor card to show Approve/Reject buttons
            'status'                => 'pay_later_pending',

            // ── Order & payment data ──────────────────────────────────────────
            'payment_status'        => OrderItem::PAYMENT_LATER,
            'order_item_id'         => $this->item->id,
            'order_id'              => $this->item->order_id,
            'total_amount'          => (float) $this->item->total_amount,
            'subtotal'              => (float) $this->item->subtotal,
            'delivery_charge'       => (float) ($this->item->delivery_charge ?? 0),
            'days_requested'        => $days,
            'payment_due_at'        => $this->item->payment_due_at?->toDateTimeString(),
            'payment_due_formatted' => $due,

            // ── Item details ──────────────────────────────────────────────────
            'product_name'          => $this->item->product->name ?? '',
            'quantity_unit'         => $this->item->quantity_unit,

            // ── Customer details (vendor needs to see who is requesting) ──────
            'customer_name'         => $this->item->order->customer->name ?? '',
            'customer_phone'        => $this->item->order->customer->phone ?? '',

            // ── Routing: Flutter uses this to know it's a vendor notification ─
            'recipient_type'        => 'vendor',
        ];
    }
}
