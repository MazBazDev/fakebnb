<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\RoleService;
use App\Services\ListingService;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function becomeHost(Request $request, RoleService $roleService)
    {
        $roleService->assignRole($request->user(), 'host');

        return response()->json([
            'message' => 'Rôle hôte activé.',
        ]);
    }

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
}
