<?php

use App\Models\Cohost;
use App\Models\Listing;
use App\Models\User;
use Laravel\Passport\Passport;

function makeHost(): User
{
    return User::factory()->create();
}

function makeListing(User $host): Listing
{
    return Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Appartement',
        'description' => 'Centre-ville',
        'city' => 'Paris',
        'address' => '10 rue de Paris',
        'price_per_night' => 120,
    ]);
}

it('forbids non-host users from listing cohosts', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/cohosts');

    $response->assertStatus(403);
});

it('allows a host to list their cohosts', function () {
    $host = makeHost();
    $listing = makeListing($host);
    $cohostUser = User::factory()->create();
    Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohostUser->id,
        'listing_id' => $listing->id,
        'can_read_conversations' => true,
    ]);

    Passport::actingAs($host);
    $response = $this->getJson('/api/v1/cohosts');

    $response->assertOk()
        ->assertJsonStructure([['id', 'host_user_id', 'cohost_user_id']]);
});

it('allows a host to create a cohost with permissions', function () {
    $host = makeHost();
    $listing = makeListing($host);
    $cohostUser = User::factory()->create();
    Passport::actingAs($host);

    $response = $this->postJson('/api/v1/cohosts', [
        'listing_id' => $listing->id,
        'cohost_email' => $cohostUser->email,
        'can_read_conversations' => true,
        'can_reply_messages' => true,
        'can_edit_listings' => false,
    ]);

    $response->assertCreated()
        ->assertJsonPath('cohost_user_id', $cohostUser->id);

    $this->assertDatabaseHas('cohosts', [
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohostUser->id,
        'listing_id' => $listing->id,
        'can_read_conversations' => true,
        'can_reply_messages' => true,
        'can_edit_listings' => false,
    ]);
});

it('prevents a host from adding themselves as cohost', function () {
    $host = makeHost();
    $listing = makeListing($host);
    Passport::actingAs($host);

    $response = $this->postJson('/api/v1/cohosts', [
        'listing_id' => $listing->id,
        'cohost_email' => $host->email,
        'can_read_conversations' => true,
    ]);

    $response->assertStatus(403);
});

it('allows a host to update cohost permissions', function () {
    $host = makeHost();
    $listing = makeListing($host);
    $cohostUser = User::factory()->create();
    $cohost = Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohostUser->id,
        'listing_id' => $listing->id,
        'can_edit_listings' => false,
    ]);
    Passport::actingAs($host);

    $response = $this->patchJson("/api/v1/cohosts/{$cohost->id}", [
        'can_edit_listings' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('can_edit_listings', true);
});

it('forbids updating cohost owned by another host', function () {
    $host = makeHost();
    $otherHost = makeHost();
    $listing = makeListing($otherHost);
    $cohostUser = User::factory()->create();
    $cohost = Cohost::create([
        'host_user_id' => $otherHost->id,
        'cohost_user_id' => $cohostUser->id,
        'listing_id' => $listing->id,
        'can_edit_listings' => false,
    ]);
    Passport::actingAs($host);

    $response = $this->patchJson("/api/v1/cohosts/{$cohost->id}", [
        'can_edit_listings' => true,
    ]);

    $response->assertStatus(403);
});

it('allows a host to delete a cohost', function () {
    $host = makeHost();
    $listing = makeListing($host);
    $cohostUser = User::factory()->create();
    $cohost = Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohostUser->id,
        'listing_id' => $listing->id,
        'can_edit_listings' => false,
    ]);
    Passport::actingAs($host);

    $response = $this->deleteJson("/api/v1/cohosts/{$cohost->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Co-hôte supprimé.']);

    $this->assertDatabaseMissing('cohosts', [
        'id' => $cohost->id,
    ]);
});
