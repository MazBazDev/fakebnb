<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ListingService
{
    public function __construct(private RoleService $roleService)
    {
    }

    public function listPublic(array $filters = [], int $perPage = 12)
    {
        $query = Listing::query()->with('images')->latest();

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($builder) use ($term) {
                $builder->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['min_guests'])) {
            $query->where('guest_capacity', '>=', (int) $filters['min_guests']);
        }

        return $query->paginate($perPage);
    }

    public function listForHost(User $user)
    {
        return Listing::query()
            ->with('images')
            ->where('host_user_id', $user->id)
            ->latest()
            ->get();
    }

    public function create(User $host, array $data): Listing
    {
        Gate::authorize('create', Listing::class);
        $this->roleService->assignRole($host, 'host');

        return Listing::create([
            'host_user_id' => $host->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'address' => $data['address'],
            'guest_capacity' => $data['guest_capacity'],
            'price_per_night' => $data['price_per_night'],
            'rules' => $data['rules'] ?? null,
            'amenities' => $data['amenities'] ?? [],
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

        $paths = $listing->images()->pluck('path')->all();
        if (! empty($paths)) {
            Storage::disk('public')->delete($paths);
        }
        Storage::disk('public')->deleteDirectory("listings/{$listing->id}");

        $listing->delete();
    }
}
