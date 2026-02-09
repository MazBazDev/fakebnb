<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'city' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'full_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'guest_capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'price_per_night' => ['sometimes', 'integer', 'min:1'],
            'cashoula_direct_enabled' => ['sometimes', 'boolean'],
            'rules' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:50'],
        ];
    }
}
