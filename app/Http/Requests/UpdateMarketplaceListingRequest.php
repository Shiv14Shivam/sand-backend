<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_per_bag'           => ['sometimes', 'numeric', 'min:0.01', 'max:99999.99'],
            'delivery_charge_per_ton' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'available_stock_bags'    => ['sometimes', 'integer', 'min:0', 'max:9999999'],
            'status'                  => ['sometimes', 'in:active,inactive'],
        ];
    }
}
