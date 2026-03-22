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
        'quantity_unit',
        'price_per_unit',
        'delivery_charge_per_km',
        'delivery_charge',          // flat fee for this item = per_ton * km * tons
        'distance_km',
        'subtotal',
        'status',
        'rejection_reason',
        'actioned_at',
        'payment_status',
        'payment_due_at',
        'paid_at',
    ];

    protected $casts = [
        'price_per_unit'           => 'decimal:2',
        'delivery_charge_per_km' => 'decimal:2',
        'delivery_charge'         => 'decimal:2',
        'subtotal'                => 'decimal:2',
        'distance_km'             => 'decimal:2',
        'actioned_at'             => 'datetime',
        'payment_due_at'          => 'datetime',
        'paid_at'                 => 'datetime',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_DECLINED  = 'declined';

    const PAYMENT_UNPAID   = 'unpaid';
    const PAYMENT_PAID     = 'paid';
    const PAYMENT_LATER    = 'pay_later';

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

    // ─── Computed helpers ─────────────────────────────────────────────

    /**
     * Total payable = subtotal + delivery_charge
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->subtotal + (float) ($this->delivery_charge ?? 0);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isPaymentPending(): bool
    {
        return $this->status === self::STATUS_ACCEPTED
            && $this->payment_status !== self::PAYMENT_PAID;
    }
}
