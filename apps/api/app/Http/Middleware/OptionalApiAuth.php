<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if ($user) {
            $request->setUserResolver(fn () => $user);
            Auth::setUser($user);
        }

        return $next($request);
    }
}
