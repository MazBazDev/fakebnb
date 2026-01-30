<?php

namespace App\Services;

use App\Models\Cohost;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class CohostService
{
    public function listForHost(User $host)
    {
        Gate::authorize('viewAny', Cohost::class);

        return Cohost::query()
            ->with('cohost')
            ->where('host_user_id', $host->id)
            ->get();
    }

    public function create(User $host, array $data): Cohost
    {
        Gate::authorize('create', Cohost::class);

        if ((int) $data['cohost_user_id'] === $host->id) {
            throw new AuthorizationException('Impossible de se définir co-hôte soi-même.');
        }

        return Cohost::create([
            'host_user_id' => $host->id,
            'cohost_user_id' => $data['cohost_user_id'],
            'can_read_conversations' => $data['can_read_conversations'] ?? false,
            'can_reply_messages' => $data['can_reply_messages'] ?? false,
            'can_edit_listings' => $data['can_edit_listings'] ?? false,
        ])->load('cohost');
    }

    public function update(User $host, Cohost $cohost, array $data): Cohost
    {
        Gate::authorize('update', $cohost);

        $cohost->fill([
            'can_read_conversations' => $data['can_read_conversations'] ?? $cohost->can_read_conversations,
            'can_reply_messages' => $data['can_reply_messages'] ?? $cohost->can_reply_messages,
            'can_edit_listings' => $data['can_edit_listings'] ?? $cohost->can_edit_listings,
        ])->save();

        return $cohost->fresh()->load('cohost');
    }

    public function delete(User $host, Cohost $cohost): void
    {
        Gate::authorize('delete', $cohost);

        $cohost->delete();
    }
}
