<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastMessage = $this->messages->sortByDesc('created_at')->first();

        return [
            'id' => $this->id,
            'listing_id' => $this->listing_id,
            'host_user_id' => $this->host_user_id,
            'guest_user_id' => $this->guest_user_id,
            'listing' => $this->whenLoaded('listing', function () {
                return [
                    'id' => $this->listing->id,
                    'title' => $this->listing->title,
                    'city' => $this->listing->city,
                ];
            }),
            'last_message' => $lastMessage ? [
                'id' => $lastMessage->id,
                'body' => $lastMessage->body,
                'sender_user_id' => $lastMessage->sender_user_id,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
