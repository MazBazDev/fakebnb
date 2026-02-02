<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Auth', 'Inscription, session et profil courant')]
class AuthController extends Controller
{
    /**
     * Inscription utilisateur.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, AuthService $authService)
    {
        $payload = $authService->register($request->validated());

        return response()->json($payload, 201);
    }

    /**
     * Utilisateur courant.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Déconnexion (révocation des tokens).
     */
    public function logout(Request $request, AuthService $authService)
    {
        $authService->logout($request->user());

        return response()->json([
            'message' => 'Déconnecté.',
        ]);
    }
}
