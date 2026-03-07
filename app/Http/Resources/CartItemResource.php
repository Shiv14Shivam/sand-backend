<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $listing = $this->listing;

        return [
            'id'            => $this->id,
            'listing_id'    => $this->listing_id,
            'quantity_bags' => $this->quantity_bags,
            'added_at'      => $this->created_at?->toISOString(),

            // Live stock check so frontend can warn the customer
            'in_stock'      => $listing && $listing->available_stock_bags >= $this->quantity_bags,

            'listing' => $this->whenLoaded('listing', function () use ($listing) {
                return [
                    'id'                      => $listing->id,
                    'status'                  => $listing->status,
                    'price_per_bag'           => $listing->price_per_bag,
                    'delivery_charge_per_ton' => $listing->delivery_charge_per_ton,
                    'available_stock_bags'    => $listing->available_stock_bags,
                    'line_total'              => round($listing->price_per_bag * $this->quantity_bags, 2),
                    'product'                 => new ProductResource($this->listing->whenLoaded('product')),
                    'brand'                   => new BrandResource($this->listing->whenLoaded('brand')),
                    'category'                => new CategoryResource($this->listing->whenLoaded('category')),
                    'seller' => $this->whenLoaded('listing', function () use ($listing) {
                        return $listing->relationLoaded('seller') ? [
                            'id'   => $listing->seller->id,
                            'name' => $listing->seller->name,
                        ] : null;
                    }),
                ];
            }),
        ];
    }
}
