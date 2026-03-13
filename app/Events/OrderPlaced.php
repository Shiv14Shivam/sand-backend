<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderPlaced implements ShouldBroadcast
{
    public $orderItem;

    public $afterCommit = true;

    public function __construct($orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('vendor.' . $this->orderItem->vendor_id);
    }

    public function broadcastAs()
    {
        return 'order.placed';
    }
}
