<?php

namespace App\Services;

use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CohostService
{
    public function listForHost(User $host, ?int $listingId = null)
    {
        Gate::authorize('viewAny', Cohost::class);

        $query = Cohost::query()
            ->with(['cohost', 'listing'])
            ->where('host_user_id', $host->id);

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        return $query->get();
    }

    public function create(User $host, array $data): Cohost
    {
        Gate::authorize('create', Cohost::class);

        $listing = Listing::findOrFail($data['listing_id']);

        if ($listing->host_user_id !== $host->id) {
            throw new AuthorizationException('Action interdite.');
        }

        $cohostUser = User::where('email', $data['cohost_email'])->first();

        if (! $cohostUser) {
            throw ValidationException::withMessages([
                'cohost_email' => ['Utilisateur introuvable.'],
            ]);
        }

        if ((int) $cohostUser->id === $host->id) {
            throw new AuthorizationException('Impossible de se définir co-hôte soi-même.');
        }

        return Cohost::create([
            'host_user_id' => $host->id,
            'cohost_user_id' => $cohostUser->id,
            'listing_id' => $listing->id,
            'can_read_conversations' => $data['can_read_conversations'] ?? false,
            'can_reply_messages' => $data['can_reply_messages'] ?? false,
            'can_edit_listings' => $data['can_edit_listings'] ?? false,
        ])->load(['cohost', 'listing']);
    }

    public function update(User $host, Cohost $cohost, array $data): Cohost
    {
        Gate::authorize('update', $cohost);

        $cohost->fill([
            'can_read_conversations' => $data['can_read_conversations'] ?? $cohost->can_read_conversations,
            'can_reply_messages' => $data['can_reply_messages'] ?? $cohost->can_reply_messages,
            'can_edit_listings' => $data['can_edit_listings'] ?? $cohost->can_edit_listings,
        ])->save();

        return $cohost->fresh()->load(['cohost', 'listing']);
    }

    public function delete(User $host, Cohost $cohost): void
    {
        Gate::authorize('delete', $cohost);

        $cohost->delete();
    }
}
