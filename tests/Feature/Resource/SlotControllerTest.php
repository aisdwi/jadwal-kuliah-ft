<?php

namespace Tests\Feature\Resource;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SlotControllerTest extends TestCase
{
    public function test_admin_jurusan_can_update_slot_with_own_jurusan_context(): void
    {
        $this->seedSlotFixture();
        DB::table('role')->updateOrInsert(['id' => 6], ['role' => 'Admin Jurusan']);
        $user = UserModel::factory()->create([
            'role_id' => 6,
            'jurusan_id' => 1,
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v2/slot/1', [
            'hari_id' => 1,
            'waktu_id' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('waktu_id', 2);

        $this->assertDatabaseHas('slot', [
            'id' => 1,
            'hari_id' => 1,
            'waktu_id' => 2,
        ]);
        $this->assertDatabaseHas('jurusan_slot', [
            'jurusan_id' => 1,
            'slot_id' => 1,
        ]);
    }

    public function test_slot_attaches_existing_day_time_to_current_jurusan_when_hidden_by_filter(): void
    {
        $this->seedSlotFixture();
        DB::table('jurusan')->insert(['id' => 2, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('jurusan_slot')->insert([
            'jurusan_id' => 2,
            'slot_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role')->updateOrInsert(['id' => 6], ['role' => 'Admin Jurusan']);
        $user = UserModel::factory()->create([
            'role_id' => 6,
            'jurusan_id' => 1,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v2/slot', [
            'hari_id' => 1,
            'waktu_id' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('id', 1)
            ->assertJsonPath('hari_id', 1)
            ->assertJsonPath('waktu_id', 1);

        $this->assertSame(1, DB::table('slot')->where('hari_id', 1)->where('waktu_id', 1)->count());
        $this->assertDatabaseHas('jurusan_slot', ['jurusan_id' => 2, 'slot_id' => 1]);
        $this->assertDatabaseHas('jurusan_slot', ['jurusan_id' => 1, 'slot_id' => 1]);
    }

    public function test_slot_save_is_idempotent_when_day_time_already_exists_for_same_jurusan(): void
    {
        $this->seedSlotFixture();
        DB::table('jurusan_slot')->insert([
            'jurusan_id' => 1,
            'slot_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role')->updateOrInsert(['id' => 6], ['role' => 'Admin Jurusan']);
        $user = UserModel::factory()->create([
            'role_id' => 6,
            'jurusan_id' => 1,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v2/slot', [
            'hari_id' => 1,
            'waktu_id' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('id', 1)
            ->assertJsonPath('hari_id', 1)
            ->assertJsonPath('waktu_id', 1);

        $this->assertSame(1, DB::table('slot')->where('hari_id', 1)->where('waktu_id', 1)->count());
        $this->assertSame(1, DB::table('jurusan_slot')->where('jurusan_id', 1)->where('slot_id', 1)->count());
    }

    private function seedSlotFixture(): void
    {
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Elektro']);
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->updateOrInsert(['id' => 1], ['pukul' => '08.00 - 09.40', 'sks' => 2]);
        DB::table('waktu')->updateOrInsert(['id' => 2], ['pukul' => '09.50 - 11.30', 'sks' => 2]);
        DB::table('slot')->insert(['id' => 1, 'hari_id' => 1, 'waktu_id' => 1]);
    }
}
