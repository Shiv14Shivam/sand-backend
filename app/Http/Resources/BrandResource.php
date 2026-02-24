<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->category_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'logo'        => $this->logo ? asset('storage/' . $this->logo) : null,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'category'    => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
