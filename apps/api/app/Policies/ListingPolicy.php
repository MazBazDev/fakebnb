<?php

namespace App\Policies;

use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user = null, Listing $listing): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isHost($user);
    }

    public function update(User $user, Listing $listing): bool
    {
        if ($listing->host_user_id === $user->id) {
            return true;
        }

        return Cohost::query()
            ->where('listing_id', $listing->id)
            ->where('cohost_user_id', $user->id)
            ->where('can_edit_listings', true)
            ->exists();
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $listing->host_user_id === $user->id;
    }

    private function isHost(User $user): bool
    {
        return $user->roles()->where('name', 'host')->exists();
    }
}
