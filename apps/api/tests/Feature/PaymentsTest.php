<?php

use App\Models\ApiToken;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;

function authHeaderForPayment(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

function bookingAwaitingPayment(): array
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

    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'status' => 'awaiting_payment',
    ]);

    return [$guest, $booking];
}

it('creates a payment intent for awaiting payment booking', function () {
    [$guest, $booking] = bookingAwaitingPayment();
    $headers = authHeaderForPayment($guest, 'payment-intent');

    $response = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.status', 'requires_authorization');
});

it('authorizes and captures a payment', function () {
    [$guest, $booking] = bookingAwaitingPayment();
    $headers = authHeaderForPayment($guest, 'payment-authorize');

    $intent = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ], $headers)->json('data');

    $response = $this->postJson("/api/v1/payments/{$intent['id']}/authorize", [], $headers);

    $response->assertOk()
        ->assertJsonPath('data.status', 'captured');

    expect($booking->fresh()->status)->toBe('confirmed');
    expect($booking->fresh()->paid_at)->not->toBeNull();
});

it('prevents intent for non awaiting payment bookings', function () {
    [$guest, $booking] = bookingAwaitingPayment();
    $booking->update(['status' => 'pending']);
    $headers = authHeaderForPayment($guest, 'payment-intent-deny');

    $response = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ], $headers);

    $response->assertStatus(403);
});
