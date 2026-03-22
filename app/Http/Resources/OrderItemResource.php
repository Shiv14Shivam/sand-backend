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
            'subtotal'                => $this->subtotal,
            'status'                  => $this->status,
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
