<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function geocode(string $query): ?array
    {
        if (! config('geocoding.enabled', true)) {
            return null;
        }

        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cacheKey = 'geocode:' . md5($query);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            try {
                $response = Http::timeout((int) config('geocoding.timeout', 5))
                    ->retry(
                        (int) config('geocoding.retries', 1),
                        (int) config('geocoding.retry_sleep', 200)
                    )
                    ->withHeaders([
                        'User-Agent' => config('app.name') . ' Geocoder',
                    ])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'json',
                        'limit' => 1,
                    ]);
            } catch (\Throwable $e) {
                return null;
            }

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
