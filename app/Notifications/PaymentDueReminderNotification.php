<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaymentDueReminderNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    // type: 'tomorrow' | 'today'
    public function __construct(
        public OrderItem $item,
        public string    $type = 'tomorrow'
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
        $due   = $this->item->payment_due_at?->format('d M Y');
        $amt   = $this->item->total_amount;

        $titles = [
            'tomorrow' => '⏰ Payment Due Tomorrow',
            'today'    => '🚨 Payment Due Today!',
        ];

        $bodies = [
            'tomorrow' => "Your payment of ₹{$amt} is due tomorrow ({$due}). Pay now to avoid issues.",
            'today'    => "Your payment of ₹{$amt} is due TODAY ({$due}). Please pay immediately!",
        ];

        return [
            'title'                 => $titles[$this->type] ?? $titles['tomorrow'],
            'body'                  => $bodies[$this->type] ?? $bodies['tomorrow'],
            'status'                => 'delivered',      // order still complete
            'payment_status'        => 'pay_later',
            'reminder_type'         => $this->type,
            'order_item_id'         => $this->item->id,
            'order_id'              => $this->item->order_id,
            'total_amount'          => $amt,
            'payment_due_at'        => $this->item->payment_due_at?->toDateTimeString(),
            'payment_due_formatted' => $due,
            'product_name'          => $this->item->product->name ?? '',
        ];
    }
}
