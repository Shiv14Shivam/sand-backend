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

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        // ✅ Load vendor.vendor to reach firm_name
        // Chain: OrderItem->vendor (User) -> vendor (Vendor model) -> firm_name
        $item   = $this->orderItem->loadMissing(['order', 'product', 'vendor.vendor']);
        $order  = $item->order;
        $user   = $item->vendor;                    // User model
        $vendor = $user?->vendor;                   // Vendor model (has firm_name)

        $statusMessages = [
            'accepted'   => 'Your order has been accepted by the vendor. Proceed to payment to confirm delivery.',
            'declined'   => 'Your order was declined by the vendor.',
            'processing' => 'Your order is being processed and will be dispatched soon.',
            'shipped'    => 'Your order is out for delivery!',
            'delivered'  => 'Your order has been delivered successfully.',
        ];

        $status         = $item->status ?? 'pending';
        $subtotal       = (float) ($item->subtotal ?? 0);
        $deliveryCharge = (float) ($item->delivery_charge ?? 0);
        $totalAmount    = $subtotal + $deliveryCharge;

        return [
            'title'            => 'Order ' . ucfirst($status),
            'body'             => $statusMessages[$status] ?? 'Your order status was updated.',
            'order_id'         => $order->id,
            'order_item_id'    => $item->id,
            'product_name'     => $item->product->name ?? '',
            'quantity_unit'    => $item->quantity_unit  ?? 0,
            'subtotal'         => $subtotal,
            'delivery_charge'  => $deliveryCharge,
            'total_amount'     => $totalAmount,
            'status'           => $status,
            'rejection_reason' => $item->rejection_reason ?? null,
            'payment_status'   => $item->payment_status  ?? 'unpaid',
            'payment_due'      => ($status === 'accepted' && ($item->payment_status ?? 'unpaid') !== 'paid')
                ? $totalAmount
                : 0,
            // ✅ firm_name from vendors table via User->vendor relationship
            'vendor_name'      => $vendor?->firm_name ?? $user?->name ?? '',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $item           = $this->orderItem->loadMissing(['order', 'product', 'vendor.vendor']);
        $status         = $item->status ?? 'pending';
        $subtotal       = (float) ($item->subtotal ?? 0);
        $deliveryCharge = (float) ($item->delivery_charge ?? 0);
        $totalAmount    = $subtotal + $deliveryCharge;

        $user   = $item->vendor;
        $vendor = $user?->vendor;

        return new BroadcastMessage([
            'title'            => 'Order ' . ucfirst($status),
            'body'             => $this->broadcastBody($status),
            'order_id'         => $item->order_id,
            'order_item_id'    => $item->id,
            'product_name'     => $item->product->name ?? '',
            'quantity_unit'    => $item->quantity_unit  ?? 0,
            'subtotal'         => $subtotal,
            'delivery_charge'  => $deliveryCharge,
            'total_amount'     => $totalAmount,
            'status'           => $status,
            'rejection_reason' => $item->rejection_reason ?? null,
            'payment_status'   => $item->payment_status  ?? 'unpaid',
            'payment_due'      => ($status === 'accepted' && ($item->payment_status ?? 'unpaid') !== 'paid')
                ? $totalAmount
                : 0,
            'vendor_name'      => $vendor?->firm_name ?? $user?->name ?? '',
        ]);
    }

    private function broadcastBody(string $status): string
    {
        return match ($status) {
            'accepted'   => 'Your order was accepted. Tap to pay.',
            'declined'   => 'Your order was declined by the vendor.',
            'processing' => 'Your order is being processed.',
            'shipped'    => 'Your order is out for delivery!',
            'delivered'  => 'Your order has been delivered.',
            default      => 'Your order status was updated.',
        };
    }
}
