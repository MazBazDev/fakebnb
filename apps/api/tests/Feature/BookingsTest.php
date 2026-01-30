<?php

use App\Models\ApiToken;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;

function authHeaderForBooking(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

function hostWithListing(): array
{
    $host = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $host->roles()->attach($role);

    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Villa',
        'description' => 'Piscine',
        'city' => 'Nice',
        'address' => '1 promenade',
        'price_per_night' => 200,
    ]);

    return [$host, $listing];
}

it('creates a booking when dates are available', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $headers = authHeaderForBooking($guest, 'booking-ok');

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(8)->toDateString(),
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.listing_id', $listing->id)
        ->assertJsonPath('data.guest_user_id', $guest->id)
        ->assertJsonPath('data.status', 'pending');
});

it('rejects overlapping bookings with 409', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();

    Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(12)->toDateString(),
        'status' => 'confirmed',
    ]);

    $headers = authHeaderForBooking(User::factory()->create(), 'booking-conflict');

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(11)->toDateString(),
        'end_date' => now()->addDays(13)->toDateString(),
    ], $headers);

    $response->assertStatus(409)
        ->assertJson(['message' => 'Dates indisponibles.']);
});

it('returns bookings for guest or host', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ]);

    $guestHeaders = authHeaderForBooking($guest, 'booking-guest');
    $hostHeaders = authHeaderForBooking($host, 'booking-host');

    $guestResponse = $this->getJson('/api/v1/bookings', $guestHeaders);
    $hostResponse = $this->getJson('/api/v1/bookings', $hostHeaders);

    $guestResponse->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);

    $hostResponse->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);
});

it('allows host to confirm a booking', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'pending',
    ]);

    $headers = authHeaderForBooking($host, 'booking-confirm');

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/confirm", [], $headers);

    $response->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

it('allows host to reject a booking', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'pending',
    ]);

    $headers = authHeaderForBooking($host, 'booking-reject');

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/reject", [], $headers);

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected');
});

it('prevents guests from confirming a booking', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'pending',
    ]);

    $headers = authHeaderForBooking($guest, 'booking-confirm-guest');

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/confirm", [], $headers);

    $response->assertStatus(403);
});
