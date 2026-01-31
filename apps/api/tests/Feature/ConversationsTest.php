<?php

use App\Models\ApiToken;
use App\Models\Listing;
use App\Models\User;

function authHeaderForConversation(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

function hostWithListingForConversation(): array
{
    $host = User::factory()->create();

    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Loft',
        'description' => 'Vue mer',
        'city' => 'Biarritz',
        'address' => '1 rue du Port',
        'price_per_night' => 120,
    ]);

    return [$host, $listing];
}

it('prevents host from creating a conversation on their listing', function () {
    [$host, $listing] = hostWithListingForConversation();
    $headers = authHeaderForConversation($host, 'conversation-host');

    $response = $this->postJson('/api/v1/conversations', [
        'listing_id' => $listing->id,
    ], $headers);

    $response->assertStatus(403);
});

it('prevents cohost from creating a conversation on the listing', function () {
    [$host, $listing] = hostWithListingForConversation();
    $cohost = User::factory()->create();

    \App\Models\Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => $cohost->id,
        'listing_id' => $listing->id,
        'can_read_conversations' => true,
        'can_reply_messages' => true,
        'can_edit_listings' => false,
    ]);

    $headers = authHeaderForConversation($cohost, 'conversation-cohost');

    $response = $this->postJson('/api/v1/conversations', [
        'listing_id' => $listing->id,
    ], $headers);

    $response->assertStatus(403);
});

it('allows guest to create a conversation', function () {
    [$host, $listing] = hostWithListingForConversation();
    $guest = User::factory()->create();
    $headers = authHeaderForConversation($guest, 'conversation-guest');

    $response = $this->postJson('/api/v1/conversations', [
        'listing_id' => $listing->id,
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.listing_id', $listing->id)
        ->assertJsonPath('data.guest_user_id', $guest->id);
});
