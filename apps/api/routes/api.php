<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\CohostController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\ListingImageController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', [StatusController::class, 'health']);

Route::prefix('v1')->group(function () {
    Route::get('/ping', [StatusController::class, 'ping']);

    Route::get('/listings', [ListingController::class, 'index'])
        ->middleware('auth.api.optional');
    Route::get('/listings/{listing}', [ListingController::class, 'show'])
        ->middleware('auth.api.optional');
    Route::get('/listings/{listing}/bookings', [ListingController::class, 'bookings'])
        ->middleware('auth.api.optional');

    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me/profile', [MeController::class, 'updateProfile']);
        Route::get('/me/listings', [MeController::class, 'myListings']);
        Route::get('/me/cohost-listings', [MeController::class, 'cohostListings']);
        Route::get('/me/host-stats', [MeController::class, 'hostStats']);

        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        Route::post('/payments/intent', [PaymentController::class, 'intent']);
        Route::post('/payments/{payment}/authorize', [PaymentController::class, 'authorizePayment']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

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
