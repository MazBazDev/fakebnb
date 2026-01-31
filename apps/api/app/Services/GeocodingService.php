<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cacheKey = 'geocode:' . md5($query);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => config('app.name') . ' Geocoder',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            if (! is_array($data) || count($data) === 0) {
                return null;
            }

            $first = $data[0] ?? null;
            if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $first['lat'],
                'lng' => (float) $first['lon'],
            ];
        });
    }
}
