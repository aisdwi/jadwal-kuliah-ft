<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Modules\Shared\Application\Service\NotificationReader;
use App\Modules\Shared\Application\Service\NotificationWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function __construct(
        private readonly NotificationReader $notifications,
        private readonly NotificationWriter $notificationWriter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $limit = (int) $request->query('limit', 10);

        return response()->json([
            'data' => $this->notifications->latestForUser($userId, $limit),
            'unread_count' => $this->notifications->unreadCountForUser($userId),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notifications->unreadCountForUser((int) $request->user()->id),
        ]);
    }

    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $this->notificationWriter->markAsRead($userId, $notificationId);

        return response()->json([
            'unread_count' => $this->notifications->unreadCountForUser($userId),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $this->notificationWriter->markAllAsRead($userId);

        return response()->json([
            'unread_count' => 0,
        ]);
    }
}
