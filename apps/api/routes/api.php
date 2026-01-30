<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\CohostController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\ListingImageController;
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
        Route::patch('/me/profile', [MeController::class, 'updateProfile']);
        Route::get('/me/listings', [MeController::class, 'myListings']);

        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject']);

        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);

        Route::post('/listings', [ListingController::class, 'store']);
        Route::patch('/listings/{listing}', [ListingController::class, 'update']);
        Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);
        Route::post('/listings/{listing}/images', [ListingImageController::class, 'store']);
        Route::patch('/listings/{listing}/images/reorder', [ListingImageController::class, 'reorder']);

        Route::get('/cohosts', [CohostController::class, 'index']);
        Route::post('/cohosts', [CohostController::class, 'store']);
        Route::patch('/cohosts/{cohost}', [CohostController::class, 'update']);
        Route::delete('/cohosts/{cohost}', [CohostController::class, 'destroy']);
    });
});
