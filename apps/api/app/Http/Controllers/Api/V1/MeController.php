<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\ListingService;
use App\Services\HostStatsService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Me', 'Endpoints liés à l’utilisateur courant')]
class MeController extends Controller
{
    /**
     * Mettre à jour le profil.
     */
    public function updateProfile(UpdateProfileRequest $request, ProfileService $profileService)
    {
        $user = $profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('photo')
        );

        return response()->json($user);
    }

    /**
     * Mes annonces (hôte).
     */
    public function myListings(Request $request, ListingService $listingService)
    {
        $perPage = (int) $request->query('per_page', 12);

        return \App\Http\Resources\ListingResource::collection(
            $listingService->listForHost($request->user(), $request->only('search'), $perPage)
        );
    }

    /**
     * Annonces co-hébergées.
     */
    public function cohostListings(Request $request, ListingService $listingService)
    {
        $perPage = (int) $request->query('per_page', 12);

        return \App\Http\Resources\ListingResource::collection(
            $listingService->listForCohost($request->user(), $request->only('search'), $perPage)
        );
    }

    /**
     * Statistiques hôte.
     */
    public function hostStats(Request $request, HostStatsService $hostStatsService)
    {
        return response()->json(
            $hostStatsService->forUser($request->user())
        );
    }
}
