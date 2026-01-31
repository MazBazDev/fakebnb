<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService)
    {
        $perPage = (int) $request->query('per_page', 15);
        $notifications = $notificationService->listForUser($request->user(), $perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request, NotificationService $notificationService)
    {
        return response()->json([
            'count' => $notificationService->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, string $notification, NotificationService $notificationService)
    {
        $notificationService->markRead($request->user(), $notification);

        return response()->noContent();
    }

    public function markAllRead(Request $request, NotificationService $notificationService)
    {
        $notificationService->markAllRead($request->user());

        return response()->noContent();
    }
}
