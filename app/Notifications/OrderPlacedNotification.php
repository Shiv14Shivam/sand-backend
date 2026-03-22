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

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stored in notifications table — read by Flutter via GET /api/notifications
    // Loads exactly the relationships that exist on your OrderItem model:
    //   order → customer         (Order belongsTo User via customer_id)
    //   order → deliveryAddress  (Order belongsTo Address via delivery_address_id)
    //   product                  (OrderItem belongsTo Product)
    //   vendor                   (OrderItem belongsTo User via vendor_id)
    // ─────────────────────────────────────────────────────────────────────────
    public function toArray($notifiable): array
    {
        // Fresh load with all relationships your models actually define
        $item = $this->orderItem->loadMissing(['order.customer', 'order.deliveryAddress', 'product', 'vendor.vendor']);

        $order   = $item->order;
        $customer = $order->customer;
        $address  = $order->deliveryAddress;

        // Build address string from your Address model columns
        $deliveryAddress = $address
            ? collect([
                $address->address_line_1 ?? null,
                $address->city           ?? null,
                $address->state          ?? null,
                $address->pincode        ?? null,
            ])->filter()->implode(', ')
            : 'Address not provided';

        // Delivery charge stored on item (after migration)
        $deliveryCharge = (float) ($item->delivery_charge ?? 0);
        $subtotal       = (float) ($item->subtotal ?? 0);
        $totalAmount    = $subtotal + $deliveryCharge;

        return [
            // ── Display ───────────────────────────────────────────────────────
            'title'            => 'New Order Received',
            'body'             => ($customer->name ?? 'A customer') . ' ordered '
                . ($item->quantity_unit ?? 0) . ' unit of '
                . ($item->product->name ?? 'your product') . '.',

            // ── Order identifiers ─────────────────────────────────────────────
            'order_id'         => $order->id,
            'order_item_id'    => $item->id,

            // ── Product & quantity ────────────────────────────────────────────
            'product_name'     => $item->product->name ?? '',
            'quantity_unit'    => $item->quantity_unit ?? 0,

            // ── Financials ────────────────────────────────────────────────────
            'price_per_unit'              => (float) ($item->price_per_unit ?? 0),
            'delivery_charge_per_km'    => (float) ($item->delivery_charge_per_km ?? 0),
            'delivery_charge'            => $deliveryCharge,
            'subtotal'                   => $subtotal,
            'total_amount'               => $totalAmount,

            // ── Distance ─────────────────────────────────────────────────────
            'distance_km'      => $item->distance_km ?? null,

            // ── Status ───────────────────────────────────────────────────────
            'status'           => $item->status,
            'payment_status'   => $item->payment_status ?? 'unpaid',

            // ── Customer info (shown to vendor) ───────────────────────────────
            'customer_name'    => $customer->name    ?? '',
            'customer_phone'   => $customer->phone   ?? '',
            'delivery_address' => $deliveryAddress,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Broadcast over WebSocket — sent to private-vendor.{id} channel
    // Keep this lean; Flutter will re-fetch full data via HTTP if needed
    // ─────────────────────────────────────────────────────────────────────────
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $item     = $this->orderItem->loadMissing(['order.customer', 'product']);
        $customer = $item->order->customer;

        return new BroadcastMessage([
            'title'         => 'New Order Received',
            'body'          => ($customer->name ?? 'A customer') . ' ordered '
                . ($item->quantity_unit ?? 0) . ' unit of '
                . ($item->product->name ?? 'your product') . '.',
            'order_id'      => $item->order_id,
            'order_item_id' => $item->id,
            'product_name'  => $item->product->name ?? '',
            'quantity_unit' => $item->quantity_unit ?? 0,
            'subtotal'      => (float) ($item->subtotal ?? 0),
            'status'        => $item->status,
            'customer_name' => $customer->name ?? '',
        ]);
    }
}
