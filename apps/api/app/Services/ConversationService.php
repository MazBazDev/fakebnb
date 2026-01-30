<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class ConversationService
{
    public function listForUser(User $user)
    {
        Gate::authorize('viewAny', Conversation::class);

        $cohostListingIds = $user->cohostedBy()
            ->where('can_read_conversations', true)
            ->pluck('listing_id');

        return Conversation::query()
            ->with(['listing', 'messages'])
            ->where('host_user_id', $user->id)
            ->orWhere('guest_user_id', $user->id)
            ->orWhereIn('listing_id', $cohostListingIds)
            ->latest()
            ->get();
    }

    public function create(User $guest, int $listingId): Conversation
    {
        Gate::authorize('create', Conversation::class);

        $listing = Listing::findOrFail($listingId);

        $isHost = $listing->host_user_id === $guest->id;
        $isCohost = Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('cohost_user_id', $guest->id)
            ->exists();

        if ($isHost || $isCohost) {
            throw new AuthorizationException('Impossible de créer une conversation avec soi-même.');
        }

        return Conversation::firstOrCreate([
            'listing_id' => $listing->id,
            'host_user_id' => $listing->host_user_id,
            'guest_user_id' => $guest->id,
        ]);
    }
}
