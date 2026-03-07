<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'total_amount' => $this->total_amount,
            'notes'        => $this->notes,
            'placed_at'    => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),

            // Item breakdown grouped by vendor for easy frontend rendering
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count'      => $this->whenLoaded('items', fn () => $this->items->count()),
            'vendors_involved' => $this->whenLoaded('items', fn () => $this->items->pluck('vendor_id')->unique()->count()),

            // Delivery address
            'delivery_address' => $this->whenLoaded('deliveryAddress', fn () => [
                'id'             => $this->deliveryAddress->id,
                'label'          => $this->deliveryAddress->label,
                'address_line_1' => $this->deliveryAddress->address_line_1,
                'address_line_2' => $this->deliveryAddress->address_line_2,
                'city'           => $this->deliveryAddress->city,
                'state'          => $this->deliveryAddress->state,
                'pincode'        => $this->deliveryAddress->pincode,
            ]),
        ];
    }
}
