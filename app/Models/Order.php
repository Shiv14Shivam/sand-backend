<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'delivery_address_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    const STATUS_PENDING            = 'pending';
    const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';
    const STATUS_COMPLETED          = 'completed';
    const STATUS_CANCELLED          = 'cancelled';

    // ─── Relationships ────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Recalculate and persist the order's overall status based on its items.
     * Called every time a vendor acts on an item.
     */
    public function recalculateStatus(): void
    {
        $items = $this->items()->get();

        $pending  = $items->where('status', OrderItem::STATUS_PENDING)->count();
        $accepted = $items->where('status', OrderItem::STATUS_ACCEPTED)->count();
        $declined = $items->where('status', OrderItem::STATUS_DECLINED)->count();
        $total    = $items->count();

        if ($pending === $total) {
            $status = self::STATUS_PENDING;
        } elseif ($accepted === $total) {
            $status = self::STATUS_COMPLETED;
        } elseif ($declined === $total) {
            // All declined — treat same as cancelled from customer perspective
            $status = self::STATUS_CANCELLED;
        } else {
            $status = self::STATUS_PARTIALLY_ACCEPTED;
        }

        $this->update(['status' => $status]);
    }
}
