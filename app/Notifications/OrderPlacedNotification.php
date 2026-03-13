<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderPlacedNotification extends Notification
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
            'title' => 'New Order',
            'message' => 'You received a new order.',
            'order_item_id' => $this->orderItem->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'New Order',
            'message' => 'You received a new order.',
            'order_item_id' => $this->orderItem->id,
        ]);
    }
}