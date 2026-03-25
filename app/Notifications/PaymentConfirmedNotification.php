<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

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
        return $this->payload();
    }

    public function toBroadcast($notifiable): array
    {
        return ['data' => $this->payload()];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.' . $this->item->order->customer_id),
            new PrivateChannel('vendor.'   . $this->item->vendor_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    private function payload(): array
    {
        return [
            'title'          => '✅ Payment Confirmed',
            'body'           => "Payment of ₹{$this->item->total_amount} received. Your order is now being processed.",
            'status'         => 'processing',
            'payment_status' => 'paid',
            'order_item_id'  => $this->item->id,
            'order_id'       => $this->item->order_id,
            'total_amount'   => $this->item->total_amount,
            'product_name'   => $this->item->product->name ?? '',
            'customer_name'  => $this->item->order->customer->name ?? '',
            'vendor_name'    => $this->item->vendor->name ?? '',
            'paid_at'        => now()->toDateTimeString(),
        ];
    }
}
