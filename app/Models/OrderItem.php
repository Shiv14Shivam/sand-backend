<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'listing_id',
        'vendor_id',
        'product_id',
        'quantity_bags',
        'price_per_bag',
        'delivery_charge_per_ton',
        'subtotal',
        'status',
        'rejection_reason',
        'actioned_at',
    ];

    protected $casts = [
        'price_per_bag'           => 'decimal:2',
        'delivery_charge_per_ton' => 'decimal:2',
        'subtotal'                => 'decimal:2',
        'actioned_at'             => 'datetime',
    ];

    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    // ─── Relationships ────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function listing()
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
