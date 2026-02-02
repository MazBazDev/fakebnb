<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\ReorderListingImagesRequest;
use App\Http\Requests\Listing\StoreListingImagesRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\ListingImageService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Listings', 'Images des annonces')]
class ListingImageController extends Controller
{
    /**
     * Upload des images d'une annonce.
     */
    public function store(
        StoreListingImagesRequest $request,
        Listing $listing,
        ListingImageService $imageService
    ) {
        $imageService->storeMany($listing, $request->file('images', []));

        return ListingResource::make($listing->load('images'))->response()->setStatusCode(201);
    }

    /**
     * Réordonner les images d'une annonce.
     */
    public function reorder(
        ReorderListingImagesRequest $request,
        Listing $listing,
        ListingImageService $imageService
    ) {
        $imageService->reorder($listing, $request->validated('image_ids'));

        return ListingResource::make($listing->load('images'));
    }
}
