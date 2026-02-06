<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: ['*'],
            headers: Request::HEADER_X_FORWARDED_TRAEFIK
        );

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            return null;
        });

        $middleware->alias([
            'auth.api.optional' => \App\Http\Middleware\OptionalApiAuth::class,
        ]);

        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->renderable(function (
            \Illuminate\Validation\ValidationException $exception,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->renderable(function (
            \Illuminate\Auth\AuthenticationException $exception,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 401);
        });

        $exceptions->renderable(function (
            \Illuminate\Auth\Access\AuthorizationException $exception,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Action interdite.',
            ], 403);
        });

        $exceptions->renderable(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Resource not found.',
            ], 404);
        });
    })->create();
