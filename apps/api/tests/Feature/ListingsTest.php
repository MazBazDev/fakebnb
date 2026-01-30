<?php

use App\Models\ApiToken;
use App\Models\Cohost;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;

function authHeaderFor(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

it('lists public listings', function () {
    $host = User::factory()->create();
    Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Cabane cosy',
        'description' => 'Un endroit calme.',
        'city' => 'Annecy',
        'address' => '1 rue du Lac',
        'price_per_night' => 120,
        'rules' => 'Pas de fêtes.',
    ]);

    $response = $this->getJson('/api/v1/listings');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'title', 'city', 'price_per_night']]]);
});

it('shows a listing detail', function () {
    $host = User::factory()->create();
    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Loft',
        'description' => 'Vue dégagée.',
        'city' => 'Lyon',
        'address' => '2 place Bellecour',
        'price_per_night' => 150,
    ]);

    $response = $this->getJson("/api/v1/listings/{$listing->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $listing->id);
});

it('prevents non-host users from creating listings', function () {
    $user = User::factory()->create();
    $headers = authHeaderFor($user, 'listing-non-host');

    $response = $this->postJson('/api/v1/listings', [
        'title' => 'Studio',
        'description' => 'Centre ville',
        'city' => 'Paris',
        'address' => '3 rue de Rivoli',
        'guest_capacity' => 2,
        'price_per_night' => 90,
    ], $headers);

    $response->assertStatus(403);
});

it('allows a host to create listings', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $user->roles()->attach($role);
    $headers = authHeaderFor($user, 'listing-host');

    $response = $this->postJson('/api/v1/listings', [
        'title' => 'Maison',
        'description' => 'Avec jardin',
        'city' => 'Nantes',
        'address' => '4 rue Verte',
        'guest_capacity' => 4,
        'price_per_night' => 110,
        'rules' => 'Non fumeur',
        'amenities' => ['wifi', 'parking'],
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.host_user_id', $user->id);
});

it('allows host owner to update listing', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $user->roles()->attach($role);
    $listing = Listing::create([
        'host_user_id' => $user->id,
        'title' => 'Maison',
        'description' => 'Avec jardin',
        'city' => 'Nantes',
        'address' => '4 rue Verte',
        'price_per_night' => 110,
        'rules' => null,
    ]);
    $headers = authHeaderFor($user, 'listing-host-update');

    $response = $this->patchJson("/api/v1/listings/{$listing->id}", [
        'price_per_night' => 130,
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.price_per_night', 130);
});

it('allows cohost with permission to update listing', function () {
    $host = User::factory()->create();
    $hostRole = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $host->roles()->attach($hostRole);
    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Studio',
        'description' => 'Cosy',
        'city' => 'Lille',
        'address' => '5 rue des Fleurs',
        'price_per_night' => 80,
    ]);

    $cohost = User::factory()->create();
    Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohost->id,
        'listing_id' => $listing->id,
        'can_edit_listings' => true,
    ]);
    $headers = authHeaderFor($cohost, 'listing-cohost-update');

    $response = $this->patchJson("/api/v1/listings/{$listing->id}", [
        'title' => 'Studio rénové',
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Studio rénové');
});

it('prevents cohost without permission from updating listing', function () {
    $host = User::factory()->create();
    $hostRole = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $host->roles()->attach($hostRole);
    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Studio',
        'description' => 'Cosy',
        'city' => 'Lille',
        'address' => '5 rue des Fleurs',
        'price_per_night' => 80,
    ]);

    $cohost = User::factory()->create();
    Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohost->id,
        'listing_id' => $listing->id,
        'can_edit_listings' => false,
    ]);
    $headers = authHeaderFor($cohost, 'listing-cohost-deny');

    $response = $this->patchJson("/api/v1/listings/{$listing->id}", [
        'title' => 'Studio rénové',
    ], $headers);

    $response->assertStatus(403);
});

it('allows host owner to delete listing', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $user->roles()->attach($role);
    $listing = Listing::create([
        'host_user_id' => $user->id,
        'title' => 'Maison',
        'description' => 'Avec jardin',
        'city' => 'Nantes',
        'address' => '4 rue Verte',
        'price_per_night' => 110,
        'rules' => null,
    ]);
    $headers = authHeaderFor($user, 'listing-host-delete');

    $response = $this->deleteJson("/api/v1/listings/{$listing->id}", [], $headers);

    $response->assertOk()
        ->assertJson(['message' => 'Annonce supprimée.']);
});
