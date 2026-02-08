<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'listing_id' => $this->listing_id,
            'guest_user_id' => $this->guest_user_id,
            'guest' => $this->whenLoaded('guest', function () {
                return [
                    'id' => $this->guest->id,
                    'name' => $this->guest->name,
                    'profile_photo_url' => $this->guest->profile_photo_url,
                ];
            }),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reply_body' => $this->reply_body,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'replied_by' => $this->whenLoaded('repliedBy', function () {
                return [
                    'id' => $this->repliedBy->id,
                    'name' => $this->repliedBy->name,
                    'profile_photo_url' => $this->repliedBy->profile_photo_url,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
