<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'listing_id'              => $this->listing_id,
            'vendor_id'               => $this->vendor_id,
            'product_id'              => $this->product_id,
            'quantity_unit'           => $this->quantity_unit,
            'price_per_unit'           => $this->price_per_unit,
            'delivery_charge_per_km' => $this->delivery_charge_per_km,
            'delivery_charge'         => (float) ($this->delivery_charge ?? 0),
            'distance_km'             => $this->distance_km,
            'subtotal'                => (float) $this->subtotal,
            'status'                  => $this->status,
            'payment_status'          => $this->payment_status ?? 'unpaid',
            'paid_at'                 => $this->paid_at?->toISOString(),
            'payment_due_at'          => $this->payment_due_at?->toISOString(),
            'days_requested'          => $this->days_requested,
            'rejection_reason'        => $this->when(
                $this->status === 'declined',
                $this->rejection_reason
            ),
            'actioned_at'             => $this->actioned_at?->toISOString(),
            'created_at'              => $this->created_at?->toISOString(),

            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
            'vendor'  => $this->whenLoaded('vendor', fn() => [
                'id'   => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
        ];
    }
}
