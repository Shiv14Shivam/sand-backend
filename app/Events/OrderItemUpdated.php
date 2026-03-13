<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderItemUpdated implements ShouldBroadcast
{
    public $orderItem;

    public $afterCommit = true;

    public function __construct($orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('customer.' . $this->orderItem->order->customer_id);
    }

    public function broadcastAs()
    {
        return 'order.updated';
    }
}
