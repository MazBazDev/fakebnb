<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class ConversationService
{
    public function listForUser(User $user)
    {
        Gate::authorize('viewAny', Conversation::class);

        $cohostHostIds = $user->cohostedBy()
            ->where('can_read_conversations', true)
            ->pluck('host_user_id');

        return Conversation::query()
            ->with(['listing', 'messages'])
            ->where('host_user_id', $user->id)
            ->orWhere('guest_user_id', $user->id)
            ->orWhereIn('host_user_id', $cohostHostIds)
            ->latest()
            ->get();
    }

    public function create(User $guest, int $listingId): Conversation
    {
        Gate::authorize('create', Conversation::class);

        $listing = Listing::findOrFail($listingId);

        if ($listing->host_user_id === $guest->id) {
            throw new AuthorizationException('Impossible de créer une conversation avec soi-même.');
        }

        return Conversation::firstOrCreate([
            'listing_id' => $listing->id,
            'host_user_id' => $listing->host_user_id,
            'guest_user_id' => $guest->id,
        ]);
    }
}
