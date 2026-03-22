<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'seller_id'                => $this->seller_id,
            'product_id'               => $this->product_id,
            'category_id'              => $this->category_id,
            'brand_id'                 => $this->brand_id,
            'price_per_unit'            => $this->price_per_unit,
            'delivery_charge_per_km'  => $this->delivery_charge_per_km,
            'available_stock_unit'     => $this->available_stock_unit,
            'status'                   => $this->status,
            'rejection_reason'         => $this->when(
                $this->status === 'rejected',
                $this->rejection_reason
            ),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
            // Relationships (loaded on demand)
            'product'                  => new ProductResource($this->whenLoaded('product')),
            'category'                 => new CategoryResource($this->whenLoaded('category')),
            'brand'                    => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
