<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Cohost;
use App\Models\Listing;
use App\Policies\BookingPolicy;
use App\Policies\CohostPolicy;
use App\Policies\ListingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Cohost::class, CohostPolicy::class);
        Gate::policy(Listing::class, ListingPolicy::class);
    }
}
