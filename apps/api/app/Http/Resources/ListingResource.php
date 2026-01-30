<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Cohost;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canBook = true;
        if ($user && $request->route()?->parameter('listing')) {
            $isHost = $this->host_user_id === $user->id;
            $isCohost = Cohost::query()
                ->where('listing_id', $this->id)
                ->where('cohost_user_id', $user->id)
                ->exists();
            $canBook = ! ($isHost || $isCohost);
        }

        return [
            'id' => $this->id,
            'host_user_id' => $this->host_user_id,
            'host' => $this->whenLoaded('host', function () {
                return [
                    'id' => $this->host->id,
                    'name' => $this->host->name,
                    'profile_photo_url' => $this->host->profile_photo_url,
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'city' => $this->city,
            'address' => $this->address,
            'guest_capacity' => $this->guest_capacity,
            'price_per_night' => $this->price_per_night,
            'rules' => $this->rules,
            'amenities' => $this->amenities ?? [],
            'can_book' => $request->route()?->parameter('listing') ? $canBook : null,
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
