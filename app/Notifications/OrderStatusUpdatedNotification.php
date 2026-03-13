<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderStatusUpdatedNotification extends Notification
{
    protected $orderItem;

    public function __construct($orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Order Update',
            'message' => 'Vendor updated your order status.',
            'order_item_id' => $this->orderItem->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'Order Update',
            'message' => 'Vendor updated your order status.',
            'order_item_id' => $this->orderItem->id,
        ]);
    }
}