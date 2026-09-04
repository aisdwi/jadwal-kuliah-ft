<?php

namespace App\Modules\Shared\Application\Service;

use Illuminate\Support\Facades\DB;

class NotificationReader
{
    public function latestForUser(int|string $userId, int $limit = 10): array
    {
        return DB::table('notifications')
            ->where('user_id', (int) $userId)
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 25)))
            ->get()
            ->map(fn ($notification) => $this->formatNotification($notification))
            ->toArray();
    }

    public function unreadCountForUser(int|string $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', (int) $userId)
            ->whereNull('read_at')
            ->count();
    }

    private function formatNotification(object $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data ? json_decode($notification->data, true) : null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
