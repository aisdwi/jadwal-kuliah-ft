<?php

namespace App\Modules\Shared\Application\Service;

use Illuminate\Support\Facades\DB;

class NotificationWriter
{
    public function createForUser(
        int|string|null $userId,
        string $type,
        string $title,
        ?string $message = null,
        array $data = [],
        int|string|null $actorId = null,
    ): void {
        if (empty($userId) || !is_numeric($userId)) {
            return;
        }

        DB::table('notifications')->insert([
            'user_id' => (int) $userId,
            'actor_id' => $actorId !== null ? (int) $actorId : null,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data === [] ? null : json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markAsRead(int|string $userId, int|string $notificationId): bool
    {
        return DB::table('notifications')
            ->where('user_id', (int) $userId)
            ->where('id', (int) $notificationId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    public function markAllAsRead(int|string $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', (int) $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
