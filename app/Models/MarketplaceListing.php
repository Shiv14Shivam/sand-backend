<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'product_id',
        'category_id',
        'brand_id',
        'price_per_unit',
        'delivery_charge_per_km',
        'available_stock_unit',
        'river_source',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'price_per_unit'           => 'decimal:2',
        'delivery_charge_per_km' => 'decimal:2',
        'available_stock_unit'    => 'integer',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING  = 'pending';
    const STATUS_REJECTED = 'rejected';

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'listing_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'listing_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
