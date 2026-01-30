<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(ListingService $listingService)
    {
        return ListingResource::collection($listingService->listPublic());
    }

    public function show(Listing $listing)
    {
        return ListingResource::make($listing->load(['images', 'host']));
    }

    public function store(StoreListingRequest $request, ListingService $listingService)
    {
        $listing = $listingService->create($request->user(), $request->validated());

        return ListingResource::make($listing)->response()->setStatusCode(201);
    }

    public function update(
        UpdateListingRequest $request,
        Listing $listing,
        ListingService $listingService
    ) {
        $updated = $listingService->update($request->user(), $listing, $request->validated());

        return ListingResource::make($updated);
    }

    public function destroy(Request $request, Listing $listing, ListingService $listingService)
    {
        $listingService->delete($request->user(), $listing);

        return response()->json([
            'message' => 'Annonce supprimée.',
        ]);
    }
}
