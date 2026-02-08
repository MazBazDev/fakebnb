<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Cohost;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class ConversationService
{
    public function listForUser(User $user)
    {
        return $this->listForGuest($user);
    }

    public function listForGuest(User $user)
    {
        Gate::authorize('viewAny', Conversation::class);

        return Conversation::query()
            ->with(['listing', 'messages'])
            ->where('guest_user_id', $user->id)
            ->latest()
            ->get();
    }

    public function listForHost(User $user, ?int $listingId = null)
    {
        Gate::authorize('viewAny', Conversation::class);

        $cohostListingIds = $user->cohostedBy()
            ->where('can_read_conversations', true)
            ->pluck('listing_id');

        $hostListingIds = Listing::query()
            ->where('host_user_id', $user->id)
            ->pluck('id');

        $allowedListingIds = $hostListingIds->merge($cohostListingIds)->unique()->values();

        $query = Conversation::query()
            ->with(['listing', 'messages'])
            ->whereIn('listing_id', $allowedListingIds)
            ->latest();

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        return $query->get();
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

    public function createForBooking(User $user, Booking $booking): Conversation
    {
        Gate::authorize('view', $booking);

        $booking->loadMissing('listing');
        $listing = $booking->listing;

        if (! $listing) {
            throw new AuthorizationException('Annonce introuvable.');
        }

        if ($user->id !== $booking->guest_user_id && $user->id !== $listing->host_user_id) {
            $canReply = Cohost::query()
                ->where('listing_id', $listing->id)
                ->where('cohost_user_id', $user->id)
                ->where('can_reply_messages', true)
                ->exists();

            if (! $canReply) {
                throw new AuthorizationException('Action interdite.');
            }
        }

        $existing = Conversation::query()
            ->where('listing_id', $listing->id)
            ->where('host_user_id', $listing->host_user_id)
            ->where('guest_user_id', $booking->guest_user_id)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Conversation::create([
            'listing_id' => $listing->id,
            'host_user_id' => $listing->host_user_id,
            'guest_user_id' => $booking->guest_user_id,
        ]);
    }
}
