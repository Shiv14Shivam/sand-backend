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
            'quantity_unit' => $this->quantity_unit,
            'added_at'      => $this->created_at?->toISOString(),

            'in_stock' => $listing &&
                $listing->available_stock_unit >= $this->quantity_unit,

            'listing' => $this->whenLoaded('listing', function () use ($listing) {
                $seller        = $listing->seller;
                // vendor's default address for distance calculation
                $vendorAddress = $seller?->addresses->first();

                return [
                    'id'                      => $listing->id,
                    'status'                  => $listing->status,
                    'price_per_unit'           => $listing->price_per_unit,
                    'delivery_charge_per_km' => $listing->delivery_charge_per_km,
                    'available_stock_unit'    => $listing->available_stock_unit,
                    'line_total'              => round(
                        $listing->price_per_unit * $this->quantity_unit,
                        2
                    ),

                    'product'  => $listing->relationLoaded('product')
                        ? new ProductResource($listing->product)
                        : null,

                    'brand'    => $listing->relationLoaded('brand')
                        ? new BrandResource($listing->brand)
                        : null,

                    'category' => $listing->relationLoaded('category')
                        ? new CategoryResource($listing->category)
                        : null,

                    'seller'   => $seller ? [
                        'id'            => $seller->id,
                        'name'          => $seller->name,
                        'phone'         => $seller->phone,
                        // ✅ These feed the Haversine formula in Flutter
                        'warehouse_lat' => $vendorAddress?->latitude,
                        'warehouse_lng' => $vendorAddress?->longitude,
                    ] : null,
                ];
            }),
        ];
    }
}
