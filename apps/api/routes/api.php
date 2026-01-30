<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CohostController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/ping', function (Request $request) {
        return response()->json([
            'message' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    });

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/{listing}', [ListingController::class, 'show']);

    Route::middleware('auth.api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/me/host', [MeController::class, 'becomeHost']);

        Route::post('/listings', [ListingController::class, 'store']);
        Route::patch('/listings/{listing}', [ListingController::class, 'update']);
        Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);

        Route::get('/cohosts', [CohostController::class, 'index']);
        Route::post('/cohosts', [CohostController::class, 'store']);
        Route::patch('/cohosts/{cohost}', [CohostController::class, 'update']);
        Route::delete('/cohosts/{cohost}', [CohostController::class, 'destroy']);
    });
});
