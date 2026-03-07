<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceDirectOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id'          => ['required', 'integer', 'exists:marketplace_listings,id'],
            'quantity_bags'       => ['required', 'integer', 'min:1', 'max:99999'],
            'delivery_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'listing_id.required'         => 'Please select a product listing.',
            'listing_id.exists'           => 'The selected listing does not exist.',
            'quantity_bags.required'      => 'Quantity is required.',
            'quantity_bags.min'           => 'Quantity must be at least 1 bag.',
            'delivery_address_id.exists'  => 'Selected delivery address does not exist.',
        ];
    }
}
