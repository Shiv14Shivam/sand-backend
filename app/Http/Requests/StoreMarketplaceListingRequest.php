<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth check via middleware
    }

    public function rules(): array
    {
        return [
            'category_id'              => ['required', 'integer', 'exists:categories,id'],
            'brand_id'                 => ['required', 'integer', 'exists:brands,id'],
            'product_id'               => ['required', 'integer', 'exists:products,id'],
            'price_per_bag'            => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'delivery_charge_per_ton'  => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'available_stock_bags'     => ['required', 'integer', 'min:1', 'max:9999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'             => 'Please select a category.',
            'category_id.exists'               => 'Selected category does not exist.',
            'brand_id.required'                => 'Please select a brand.',
            'brand_id.exists'                  => 'Selected brand does not exist.',
            'product_id.required'              => 'Please select a product.',
            'product_id.exists'                => 'Selected product does not exist.',
            'price_per_bag.required'           => 'Price per bag is required.',
            'price_per_bag.numeric'            => 'Price per bag must be a valid number.',
            'price_per_bag.min'                => 'Price per bag must be greater than 0.',
            'delivery_charge_per_ton.numeric'  => 'Delivery charge must be a valid number.',
            'delivery_charge_per_ton.min'      => 'Delivery charge cannot be negative.',
            'available_stock_bags.required'    => 'Available stock is required.',
            'available_stock_bags.integer'     => 'Available stock must be a whole number.',
            'available_stock_bags.min'         => 'Available stock must be at least 1.',
        ];
    }
}
