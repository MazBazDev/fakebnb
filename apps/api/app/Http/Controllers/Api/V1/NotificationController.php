<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Notifications', 'Notifications in-app et compteurs')]
class NotificationController extends Controller
{
    /**
     * Liste des notifications.
     */
    public function index(Request $request, NotificationService $notificationService)
    {
        $perPage = (int) $request->query('per_page', 15);
        $notifications = $notificationService->listForUser($request->user(), $perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * Nombre de notifications non lues.
     */
    public function unreadCount(Request $request, NotificationService $notificationService)
    {
        return response()->json([
            'count' => $notificationService->unreadCount($request->user()),
        ]);
    }

    /**
     * Marquer une notification comme lue.
     */
    public function markRead(Request $request, string $notification, NotificationService $notificationService)
    {
        $notificationService->markRead($request->user(), $notification);

        return response()->noContent();
    }

    /**
     * Tout marquer comme lu.
     */
    public function markAllRead(Request $request, NotificationService $notificationService)
    {
        $notificationService->markAllRead($request->user());

        return response()->noContent();
    }
}
