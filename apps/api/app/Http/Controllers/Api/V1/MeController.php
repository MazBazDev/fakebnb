<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
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
}
