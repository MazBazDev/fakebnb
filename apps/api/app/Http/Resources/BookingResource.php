<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'listing_id' => $this->listing_id,
            'guest_user_id' => $this->guest_user_id,
            'guest' => $this->whenLoaded('guest', function () {
                return [
                    'id' => $this->guest->id,
                    'name' => $this->guest->name,
                    'profile_photo_url' => $this->guest->profile_photo_url,
                ];
            }),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
