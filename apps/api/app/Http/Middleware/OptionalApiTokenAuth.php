<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;

        if (! $token) {
            return $next($request);
        }

        $tokenHash = hash('sha256', $token);
        $apiToken = ApiToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $apiToken || ! $apiToken->user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_token', $apiToken);
        $request->setUserResolver(fn () => $apiToken->user);
        Auth::setUser($apiToken->user);

        return $next($request);
    }
}
