<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PayLaterRequestedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public OrderItem $item, public int $daysRequested) {}

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
        // Only vendor gets this — they must approve/reject
        return [new PrivateChannel('vendor.' . $this->item->vendor_id)];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    private function payload(): array
    {
        $days = $this->daysRequested;
        $due  = $this->item->payment_due_at?->format('d M Y');

        return [
            'title'                 => '⏰ Pay Later Requested',
            'body'                  => "Customer wants {$days} day(s) to pay ₹{$this->item->total_amount}. Due: {$due}. Accept or reject.",
            'status'                => 'pay_later_pending',
            'payment_status'        => 'pay_later',
            'order_item_id'         => $this->item->id,
            'order_id'              => $this->item->order_id,
            'total_amount'          => $this->item->total_amount,
            'days_requested'        => $days,
            'payment_due_at'        => $this->item->payment_due_at?->toDateTimeString(),
            'payment_due_formatted' => $due,
            'product_name'          => $this->item->product->name ?? '',
            'quantity_unit'         => $this->item->quantity_unit,
            'customer_name'         => $this->item->order->customer->name ?? '',
            'customer_phone'        => $this->item->order->customer->phone ?? '',
        ];
    }
}
