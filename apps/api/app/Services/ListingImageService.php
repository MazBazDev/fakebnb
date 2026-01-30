<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ListingImageService
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function storeMany(Listing $listing, array $files): \Illuminate\Support\Collection
    {
        Gate::authorize('update', $listing);

        return DB::transaction(function () use ($listing, $files) {
            $position = (int) $listing->images()->max('position');

            $images = collect();

            foreach ($files as $file) {
                $path = Storage::disk('public')->putFile("listings/{$listing->id}", $file);
                $position++;

                $images->push(ListingImage::create([
                    'listing_id' => $listing->id,
                    'path' => $path,
                    'position' => $position,
                ]));
            }

            return $images;
        });
    }

    public function reorder(Listing $listing, array $imageIds): \Illuminate\Support\Collection
    {
        Gate::authorize('update', $listing);

        return DB::transaction(function () use ($listing, $imageIds) {
            $images = ListingImage::query()
                ->where('listing_id', $listing->id)
                ->whereIn('id', $imageIds)
                ->get()
                ->keyBy('id');

            if ($images->count() !== count($imageIds)) {
                abort(422, 'Images invalides.');
            }

            foreach ($imageIds as $index => $imageId) {
                $images[$imageId]->update(['position' => $index + 1]);
            }

            return $listing->images()->get();
        });
    }
}
