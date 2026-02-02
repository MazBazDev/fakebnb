<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Message;
use App\Policies\BookingPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CohostPolicy;
use App\Policies\ListingPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\MessagePolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Cohost::class, CohostPolicy::class);
        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'Bearer')
                        ->as('bearerAuth')
                        ->setDescription('OAuth2 Bearer token')
                );
            });
    }
}
