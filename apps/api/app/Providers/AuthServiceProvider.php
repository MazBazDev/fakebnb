<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Passport\Client;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Passport::useClientModel(Client::class);
        Passport::authorizationView('oauth.authorize');

        $accessTtl = (int) env('PASSPORT_ACCESS_TOKEN_TTL', 60);
        $refreshTtl = (int) env('PASSPORT_REFRESH_TOKEN_TTL', 43200);
        $personalTtl = (int) env('PASSPORT_PERSONAL_ACCESS_TOKEN_TTL', 525600);

        Passport::tokensExpireIn(now()->addMinutes($accessTtl));
        Passport::refreshTokensExpireIn(now()->addMinutes($refreshTtl));
        Passport::personalAccessTokensExpireIn(now()->addMinutes($personalTtl));
    }
}
