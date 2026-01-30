<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ListingService
{
    public function listPublic()
    {
        return Listing::query()->latest()->get();
    }

    public function create(User $host, array $data): Listing
    {
        Gate::authorize('create', Listing::class);

        return Listing::create([
            'host_user_id' => $host->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'address' => $data['address'],
            'price_per_night' => $data['price_per_night'],
            'rules' => $data['rules'] ?? null,
        ]);
    }

    public function update(User $user, Listing $listing, array $data): Listing
    {
        Gate::authorize('update', $listing);

        $listing->fill($data)->save();

        return $listing->fresh();
    }

    public function delete(User $user, Listing $listing): void
    {
        Gate::authorize('delete', $listing);

        $listing->delete();
    }
}
