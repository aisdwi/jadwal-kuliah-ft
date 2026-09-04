<?php

namespace Tests\Feature\Notifications;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    public function test_user_can_list_only_their_notifications_with_unread_count(): void
    {
        $user = UserModel::create([
            'nama_user' => 'Admin Elektro',
            'email' => 'admin-elektro@example.com',
            'password' => 'password',
        ]);
        $otherUser = UserModel::create([
            'nama_user' => 'Admin Sipil',
            'email' => 'admin-sipil@example.com',
            'password' => 'password',
        ]);

        DB::table('notifications')->insert([
            [
                'user_id' => $user->id,
                'actor_id' => $user->id,
                'type' => 'schedule_generation_completed',
                'title' => 'Generate jadwal selesai',
                'message' => '121/121 kelas berhasil dijadwalkan.',
                'data' => json_encode(['url' => '/scheduling/auto']),
                'read_at' => null,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'user_id' => $otherUser->id,
                'actor_id' => $otherUser->id,
                'type' => 'schedule_generation_failed',
                'title' => 'Generate jadwal gagal',
                'message' => 'Proses generate berhenti.',
                'data' => json_encode(['url' => '/scheduling/auto']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Generate jadwal selesai');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = UserModel::create([
            'nama_user' => 'Admin Elektro',
            'email' => 'admin-elektro-read@example.com',
            'password' => 'password',
        ]);

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'type' => 'schedule_generation_completed',
            'title' => 'Generate jadwal selesai',
            'message' => '121/121 kelas berhasil dijadwalkan.',
            'data' => json_encode(['url' => '/scheduling/auto']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/v2/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }
}
