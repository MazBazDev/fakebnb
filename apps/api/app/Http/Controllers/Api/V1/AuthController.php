<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use App\Models\User;

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

    /**
     * Connexion rapide (demo).
     *
     * @unauthenticated
     */
    public function devLogin(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:client,host,cohost'],
        ]);

        $email = match ($data['role']) {
            'client' => 'tc@t.fr',
            'host' => 'th@t.fr',
            'cohost' => 'tch@t.fr',
        };

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $tokenResult = $user->createToken('dev-login');
        $expiresIn = $tokenResult->token->expires_at?->diffInSeconds(now());

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'refresh_token' => null,
            'expires_in' => $expiresIn ?? 3600,
            'user' => $user,
        ]);
    }
}
