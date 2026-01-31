<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\ListingService;
use App\Services\HostStatsService;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request, ProfileService $profileService)
    {
        $user = $profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('photo')
        );

        return response()->json($user);
    }

    public function myListings(Request $request, ListingService $listingService)
    {
        return \App\Http\Resources\ListingResource::collection(
            $listingService->listForHost($request->user())
        );
    }

    public function hostStats(Request $request, HostStatsService $hostStatsService)
    {
        return response()->json(
            $hostStatsService->forUser($request->user())
        );
    }
}
