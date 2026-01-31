<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ListingService
{
    public function __construct(private GeocodingService $geocodingService)
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

        if (! empty($filters['bounds'])) {
            $bounds = $this->parseBounds($filters['bounds']);
            $paddingKm = isset($filters['padding_km']) ? (float) $filters['padding_km'] : 0.0;

            if ($bounds) {
                [$swLng, $swLat, $neLng, $neLat] = $bounds;
                if ($paddingKm > 0) {
                    $centerLat = ($swLat + $neLat) / 2;
                    $latPadding = $paddingKm / 111;
                    $lngPadding = $paddingKm / max(1, (111 * cos(deg2rad($centerLat))));

                    $swLat -= $latPadding;
                    $neLat += $latPadding;
                    $swLng -= $lngPadding;
                    $neLng += $lngPadding;
                }

                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereBetween('latitude', [$swLat, $neLat])
                    ->whereBetween('longitude', [$swLng, $neLng]);
            }
        }

        return $query->paginate($perPage);
    }

    private function parseBounds(string $bounds): ?array
    {
        $parts = array_map('trim', explode(',', $bounds));
        if (count($parts) !== 4) {
            return null;
        }

        [$swLng, $swLat, $neLng, $neLat] = array_map('floatval', $parts);

        if ($swLat > $neLat || $swLng > $neLng) {
            return null;
        }

        return [$swLng, $swLat, $neLng, $neLat];
    }

    public function listForHost(User $user, array $filters = [], int $perPage = 12)
    {
        $query = Listing::query()
            ->with('images')
            ->where('host_user_id', $user->id)
            ->latest();

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($builder) use ($term) {
                $builder->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function create(User $host, array $data): Listing
    {
        Gate::authorize('create', Listing::class);

        $fullAddress = $data['full_address'] ?? null;
        $geoQuery = $fullAddress ?: trim($data['address'] . ', ' . $data['city']);

        if (empty($data['latitude']) && empty($data['longitude'])) {
            $coords = $this->geocodingService->geocode($geoQuery);
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        return Listing::create([
            'host_user_id' => $host->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'address' => $data['address'],
            'full_address' => $fullAddress,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'guest_capacity' => $data['guest_capacity'],
            'price_per_night' => $data['price_per_night'],
            'rules' => $data['rules'] ?? null,
            'amenities' => $data['amenities'] ?? [],
        ]);
    }

    public function update(User $user, Listing $listing, array $data): Listing
    {
        Gate::authorize('update', $listing);

        $shouldGeocode = array_key_exists('address', $data)
            || array_key_exists('city', $data)
            || array_key_exists('full_address', $data);

        if ($shouldGeocode && ! array_key_exists('latitude', $data) && ! array_key_exists('longitude', $data)) {
            $fullAddress = $data['full_address'] ?? $listing->full_address;
            $address = $data['address'] ?? $listing->address;
            $city = $data['city'] ?? $listing->city;
            $geoQuery = $fullAddress ?: trim($address . ', ' . $city);
            $coords = $this->geocodingService->geocode($geoQuery);
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

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
