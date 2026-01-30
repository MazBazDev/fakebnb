<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

    public function login(LoginRequest $request, AuthService $authService)
    {
        $payload = $authService->login($request->string('email'), $request->string('password'));

        return response()->json($payload);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request, AuthService $authService)
    {
        $authService->logout($request->attributes->get('api_token'));

        return response()->json([
            'message' => 'Déconnecté.',
        ]);
    }
}
