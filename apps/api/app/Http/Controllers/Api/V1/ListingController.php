<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\BookingService;
use App\Services\ListingService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Listings', 'Annonces publiques et gestion hôte')]
class ListingController extends Controller
{
    private const LISTINGS_CACHE_TTL = 60;

    /**
     * Liste publique des annonces.
     *
     * @unauthenticated
     */
    public function index(Request $request, ListingService $listingService)
    {
        $filters = $request->only(['search', 'city', 'min_guests', 'bounds', 'padding_km']);
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 60));

        $resource = ListingResource::collection($listingService->listPublic($filters, $perPage));
        $response = $resource->response();
        $payload = $response->getData(true);

        return $this->withCacheHeaders($request, $response, $payload, true);
    }

    /**
     * Détail d'une annonce.
     *
     * @unauthenticated
     */
    public function show(Request $request, Listing $listing)
    {
        $resource = ListingResource::make($listing->load(['images', 'host']));
        $response = $resource->response();
        $payload = $response->getData(true);

        $isPublic = ! $request->user();

        return $this->withCacheHeaders($request, $response, $payload, $isPublic);
    }

    /**
     * Réservations confirmées pour une annonce.
     *
     * @unauthenticated
     */
    public function bookings(Listing $listing, BookingService $bookingService)
    {
        $bookings = $bookingService->listConfirmedForListing($listing);

        return BookingResource::collection($bookings);
    }

    /**
     * Créer une annonce.
     */
    public function store(StoreListingRequest $request, ListingService $listingService)
    {
        $listing = $listingService->create($request->user(), $request->validated());

        return ListingResource::make($listing)->response()->setStatusCode(201);
    }

    /**
     * Mettre à jour une annonce.
     */
    public function update(
        UpdateListingRequest $request,
        Listing $listing,
        ListingService $listingService
    ) {
        $updated = $listingService->update($request->user(), $listing, $request->validated());

        return ListingResource::make($updated);
    }

    /**
     * Supprimer une annonce.
     */
    public function destroy(Request $request, Listing $listing, ListingService $listingService)
    {
        $listingService->delete($request->user(), $listing);

        return response()->json([
            'message' => 'Annonce supprimée.',
        ]);
    }

    private function withCacheHeaders(Request $request, $response, array $payload, bool $public)
    {
        $etag = '"'.sha1(json_encode($payload)).'"';

        $response->setEtag($etag);
        $response->headers->set(
            'Cache-Control',
            ($public ? 'public' : 'private')
            .', max-age='
            .self::LISTINGS_CACHE_TTL
            .', must-revalidate'
        );

        if (! $public) {
            $response->headers->set('Vary', 'Authorization');
        }

        $response->isNotModified($request);

        return $response;
    }
}
