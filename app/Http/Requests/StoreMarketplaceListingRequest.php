<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'             => ['required', 'integer', 'exists:categories,id'],
            'brand_id'                => ['nullable', 'integer', 'exists:brands,id'],
            'product_id'              => ['required', 'integer', 'exists:products,id'],
            'price_per_unit'           => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'delivery_charge_per_km' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'available_stock_unit'    => ['required', 'integer', 'min:1', 'max:9999999'],
            'river_source'            => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'          => 'Please select a category.',
            'category_id.exists'            => 'Selected category does not exist.',
            'brand_id.exists'               => 'Selected brand does not exist.',
            'product_id.required'           => 'Please select a product.',
            'product_id.exists'             => 'Selected product does not exist.',
            'price_per_unit.required'        => 'Price per unit is required.',
            'price_per_unit.numeric'         => 'Price per unit must be a valid number.',
            'price_per_unit.min'             => 'Price per unit must be greater than 0.',
            'delivery_charge_per_km.numeric' => 'Delivery charge must be a valid number.',
            'delivery_charge_per_km.min'   => 'Delivery charge cannot be negative.',
            'available_stock_unit.required' => 'Available stock is required.',
            'available_stock_unit.integer'  => 'Available stock must be a whole number.',
            'available_stock_unit.min'      => 'Available stock must be at least 1.',
            'river_source.max'              => 'River source must not exceed 255 characters.',
        ];
    }
}
