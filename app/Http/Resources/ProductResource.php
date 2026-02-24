<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'category_id'          => $this->category_id,
            'brand_id'             => $this->brand_id,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'short_description'    => $this->short_description,
            'detailed_description' => $this->detailed_description,
            'unit'                 => $this->unit,
            'unit_weight'          => $this->unit_weight,
            'is_active'            => $this->is_active,
            'sort_order'           => $this->sort_order,
            'specifications'       => ProductSpecificationResource::collection(
                $this->whenLoaded('specifications')
            ),
            'category'             => new CategoryResource($this->whenLoaded('category')),
            'brand'                => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
