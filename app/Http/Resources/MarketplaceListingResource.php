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
            'price_per_bag'            => $this->price_per_bag,
            'delivery_charge_per_ton'  => $this->delivery_charge_per_ton,
            'available_stock_bags'     => $this->available_stock_bags,
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
