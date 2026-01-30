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
            'guest_capacity' => $this->guest_capacity,
            'price_per_night' => $this->price_per_night,
            'rules' => $this->rules,
            'amenities' => $this->amenities ?? [],
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path),
                        'position' => $image->position,
                    ];
                })->values();
            }, []),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
