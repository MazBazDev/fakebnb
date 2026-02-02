<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $authService)
    {
        $payload = $authService->register($request->validated());

        return response()->json($payload, 201);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request, AuthService $authService)
    {
        $authService->logout($request->user());

        return response()->json([
            'message' => 'Déconnecté.',
        ]);
    }
}
