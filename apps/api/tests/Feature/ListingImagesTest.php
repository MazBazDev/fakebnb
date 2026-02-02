<?php

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

function hostWithListingForImages(): array
{
    $host = User::factory()->create();

    $listing = Listing::create([
        'host_user_id' => $host->id,
        'title' => 'Loft',
        'description' => 'Vue dégagée.',
        'city' => 'Lyon',
        'address' => '2 place Bellecour',
        'price_per_night' => 150,
    ]);

    return [$host, $listing];
}

it('allows host to upload multiple images', function () {
    Storage::fake('public');
    [$host, $listing] = hostWithListingForImages();
    Passport::actingAs($host);

    $response = $this->postJson("/api/v1/listings/{$listing->id}/images", [
        'images' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.id', $listing->id)
        ->assertJsonCount(2, 'data.images');

    $this->assertDatabaseCount('listing_images', 2);

    $image = ListingImage::first();
    Storage::disk('public')->assertExists($image->path);
});

it('rejects image upload for non-host', function () {
    Storage::fake('public');
    [, $listing] = hostWithListingForImages();
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson("/api/v1/listings/{$listing->id}/images", [
        'images' => [
            UploadedFile::fake()->image('a.jpg'),
        ],
    ]);

    $response->assertStatus(403);
});

it('reorders images for a listing', function () {
    Storage::fake('public');
    [$host, $listing] = hostWithListingForImages();
    Passport::actingAs($host);

    $this->postJson("/api/v1/listings/{$listing->id}/images", [
        'images' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
            UploadedFile::fake()->image('c.jpg'),
        ],
    ])->assertCreated();

    $imageIds = $listing->images()->pluck('id')->all();
    $reversed = array_reverse($imageIds);

    $response = $this->patchJson("/api/v1/listings/{$listing->id}/images/reorder", [
        'image_ids' => $reversed,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.images.0.id', $reversed[0])
        ->assertJsonPath('data.images.2.id', $reversed[2]);

    $positions = ListingImage::query()
        ->where('listing_id', $listing->id)
        ->orderBy('position')
        ->pluck('id')
        ->all();

    expect($positions)->toBe($reversed);
});
