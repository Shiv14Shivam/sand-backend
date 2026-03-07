<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclineOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Please provide a reason for declining this order.',
        ];
    }
}
