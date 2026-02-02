<?php

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Laravel\Passport\Passport;

function hostWithListing(): array
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

    return [$host, $listing];
}

it('creates a booking when dates are available', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    Passport::actingAs($guest);

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(8)->toDateString(),
    ]);

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

    Passport::actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(11)->toDateString(),
        'end_date' => now()->addDays(13)->toDateString(),
    ]);

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

    Passport::actingAs($guest);
    $guestResponse = $this->getJson('/api/v1/bookings');

    Passport::actingAs($host);
    $hostResponse = $this->getJson('/api/v1/bookings');

    $guestResponse->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);

    $hostResponse->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);
});

it('allows guest to cancel a booking and refunds when paid', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'confirmed',
    ]);

    \App\Models\Payment::create([
        'booking_id' => $booking->id,
        'guest_user_id' => $guest->id,
        'host_user_id' => $host->id,
        'amount_total' => 300,
        'amount_base' => 250,
        'amount_vat' => 30,
        'amount_service' => 20,
        'commission_amount' => 30,
        'payout_amount' => 220,
        'status' => 'captured',
    ]);

    Passport::actingAs($guest);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.payment.status', 'refunded');
});

it('allows host to cancel a booking', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'confirmed',
    ]);
    Passport::actingAs($host);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('prevents host from booking their own listing', function () {
    [$host, $listing] = hostWithListing();
    Passport::actingAs($host);

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ]);

    $response->assertStatus(403);
});

it('prevents cohost from booking the listing', function () {
    [$host, $listing] = hostWithListing();
    $cohost = User::factory()->create();

    \App\Models\Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohost->id,
        'listing_id' => $listing->id,
        'can_read_conversations' => true,
        'can_reply_messages' => true,
        'can_edit_listings' => false,
    ]);
    Passport::actingAs($cohost);

    $response = $this->postJson('/api/v1/bookings', [
        'listing_id' => $listing->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ]);

    $response->assertStatus(403);
});

it('moves a booking to awaiting payment when host confirms', function () {
    [$host, $listing] = hostWithListing();
    $guest = User::factory()->create();
    $booking = Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'status' => 'pending',
    ]);
    Passport::actingAs($host);

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/confirm");

    $response->assertOk()
        ->assertJsonPath('data.status', 'awaiting_payment');
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
    Passport::actingAs($host);

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/reject");

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
    Passport::actingAs($guest);

    $response = $this->patchJson("/api/v1/bookings/{$booking->id}/confirm");

    $response->assertStatus(403);
});
