<?php

use App\Models\ApiToken;
use App\Models\Booking;
use App\Models\Cohost;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function authHeaderForDeletion(User $user, string $plainToken): array
{
    ApiToken::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return ['Authorization' => "Bearer {$plainToken}"];
}

it('deletes listing with cascades and storage cleanup', function () {
    Storage::fake('public');

    $host = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'host'], ['label' => 'Hôte']);
    $host->roles()->attach($role);

    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Maison',
        'description' => 'Avec jardin',
        'city' => 'Nantes',
        'address' => '4 rue Verte',
        'price_per_night' => 110,
    ]);

    $path = Storage::disk('public')->putFile("listings/{$listing->id}", UploadedFile::fake()->image('test.jpg'));
    ListingImage::create([
        'listing_id' => $listing->id,
        'path' => $path,
        'position' => 1,
    ]);

    $guest = User::factory()->create();
    Booking::create([
        'listing_id' => $listing->id,
        'guest_user_id' => $guest->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
        'status' => 'pending',
    ]);

    Cohost::create([
        'host_user_id' => $host->id,
        'cohost_user_id' => User::factory()->create()->id,
        'listing_id' => $listing->id,
        'can_read_conversations' => true,
        'can_reply_messages' => false,
        'can_edit_listings' => false,
    ]);

    $conversation = Conversation::create([
        'listing_id' => $listing->id,
        'host_user_id' => $host->id,
        'guest_user_id' => $guest->id,
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_user_id' => $guest->id,
        'body' => 'Hello',
    ]);

    $headers = authHeaderForDeletion($host, 'listing-delete');
    $response = $this->deleteJson("/api/v1/listings/{$listing->id}", [], $headers);

    $response->assertOk();

    expect(Listing::query()->whereKey($listing->id)->exists())->toBeFalse();
    expect(Booking::query()->where('listing_id', $listing->id)->exists())->toBeFalse();
    expect(Cohost::query()->where('listing_id', $listing->id)->exists())->toBeFalse();
    expect(Conversation::query()->where('listing_id', $listing->id)->exists())->toBeFalse();
    expect(Message::query()->where('conversation_id', $conversation->id)->exists())->toBeFalse();
    expect(ListingImage::query()->where('listing_id', $listing->id)->exists())->toBeFalse();

    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertMissing("listings/{$listing->id}");
});
