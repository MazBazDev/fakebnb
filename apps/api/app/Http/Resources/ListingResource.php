<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'host_user_id' => $this->host_user_id,
            'title' => $this->title,
            'description' => $this->description,
            'city' => $this->city,
            'address' => $this->address,
            'price_per_night' => $this->price_per_night,
            'rules' => $this->rules,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
