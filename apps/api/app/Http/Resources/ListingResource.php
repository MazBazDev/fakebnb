<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Cohost;
use App\Models\Conversation;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canBook = true;
        $conversationId = null;
        $cohostPermissions = null;
        if ($user && $request->route()?->parameter('listing')) {
            $isHost = $this->host_user_id === $user->id;
            $isCohost = Cohost::query()
                ->where('listing_id', $this->id)
                ->where('cohost_user_id', $user->id)
                ->exists();
            $canBook = ! ($isHost || $isCohost);

            $conversationId = Conversation::query()
                ->where('listing_id', $this->id)
                ->where('guest_user_id', $user->id)
                ->value('id');
        }

        if ($user && $this->relationLoaded('cohosts')) {
            $cohost = $this->cohosts->first();
            if ($cohost) {
                $cohostPermissions = [
                    'can_read_conversations' => (bool) $cohost->can_read_conversations,
                    'can_reply_messages' => (bool) $cohost->can_reply_messages,
                    'can_edit_listings' => (bool) $cohost->can_edit_listings,
                ];
            }
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
            'full_address' => $this->full_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'guest_capacity' => $this->guest_capacity,
            'price_per_night' => $this->price_per_night,
            'cashoula_direct_enabled' => (bool) $this->cashoula_direct_enabled,
            'rules' => $this->rules,
            'amenities' => $this->amenities ?? [],
            'can_book' => $request->route()?->parameter('listing') ? $canBook : null,
            'conversation_id' => $request->route()?->parameter('listing') ? $conversationId : null,
            'cohost_permissions' => $cohostPermissions,
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
