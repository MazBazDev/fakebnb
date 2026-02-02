<?php

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Laravel\Passport\Passport;

function bookingAwaitingPayment(): array
{
    $host = User::factory()->create();

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
    Passport::actingAs($guest);

    $response = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.status', 'requires_authorization');
});

it('authorizes and captures a payment', function () {
    [$guest, $booking] = bookingAwaitingPayment();
    Passport::actingAs($guest);

    $intent = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ])->json('data');

    $response = $this->postJson("/api/v1/payments/{$intent['id']}/authorize");

    $response->assertOk()
        ->assertJsonPath('data.status', 'captured');

    expect($booking->fresh()->status)->toBe('confirmed');
    expect($booking->fresh()->paid_at)->not->toBeNull();
});

it('prevents intent for non awaiting payment bookings', function () {
    [$guest, $booking] = bookingAwaitingPayment();
    $booking->update(['status' => 'pending']);
    Passport::actingAs($guest);

    $response = $this->postJson('/api/v1/payments/intent', [
        'booking_id' => $booking->id,
    ]);

    $response->assertStatus(403);
});
