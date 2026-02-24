<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSpecificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'key'        => $this->key,
            'value'      => $this->value,
            'sort_order' => $this->sort_order,
        ];
    }
}
