<?php

use App\Models\ApiToken;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;

function authHeaderForUser(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

function makeHost(): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $user->roles()->attach($role);

    return $user;
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
    $headers = authHeaderForUser($user, 'cohosts-non-host');

    $response = $this->getJson('/api/v1/cohosts', $headers);

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

    $headers = authHeaderForUser($host, 'cohosts-host');
    $response = $this->getJson('/api/v1/cohosts', $headers);

    $response->assertOk()
        ->assertJsonStructure([['id', 'host_user_id', 'cohost_user_id']]);
});

it('allows a host to create a cohost with permissions', function () {
    $host = makeHost();
    $listing = makeListing($host);
    $cohostUser = User::factory()->create();
    $headers = authHeaderForUser($host, 'cohosts-create');

    $response = $this->postJson('/api/v1/cohosts', [
        'listing_id' => $listing->id,
        'cohost_email' => $cohostUser->email,
        'can_read_conversations' => true,
        'can_reply_messages' => true,
        'can_edit_listings' => false,
    ], $headers);

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
    $headers = authHeaderForUser($host, 'cohosts-self');

    $response = $this->postJson('/api/v1/cohosts', [
        'listing_id' => $listing->id,
        'cohost_email' => $host->email,
        'can_read_conversations' => true,
    ], $headers);

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
    $headers = authHeaderForUser($host, 'cohosts-update');

    $response = $this->patchJson("/api/v1/cohosts/{$cohost->id}", [
        'can_edit_listings' => true,
    ], $headers);

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
    $headers = authHeaderForUser($host, 'cohosts-update-deny');

    $response = $this->patchJson("/api/v1/cohosts/{$cohost->id}", [
        'can_edit_listings' => true,
    ], $headers);

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
    $headers = authHeaderForUser($host, 'cohosts-delete');

    $response = $this->deleteJson("/api/v1/cohosts/{$cohost->id}", [], $headers);

    $response->assertOk()
        ->assertJson(['message' => 'Co-hôte supprimé.']);

    $this->assertDatabaseMissing('cohosts', [
        'id' => $cohost->id,
    ]);
});
