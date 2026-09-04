<?php

namespace Tests\Feature\Resource;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RuanganControllerJurusanTest extends TestCase
{
    public function test_unrestricted_user_can_assign_room_to_multiple_jurusans(): void
    {
        $this->actingAsRole('Super Admin');
        $gedungId = $this->seedGedung();
        [$elektroId, $mesinId] = $this->seedJurusans();

        $roomId = $this->postJson('/api/v2/ruangan', [
            'gedung_id' => $gedungId,
            'ruangan' => 'Vcon',
            'kapasitas' => 60,
            'jurusan_ids' => [$elektroId, $mesinId],
        ])
            ->assertCreated()
            ->assertJsonPath('jurusan_ids', [$elektroId, $mesinId])
            ->json('id');

        $this->assertDatabaseHas('jurusan_ruangan', ['jurusan_id' => $elektroId, 'ruangan_id' => $roomId]);
        $this->assertDatabaseHas('jurusan_ruangan', ['jurusan_id' => $mesinId, 'ruangan_id' => $roomId]);
    }

    public function test_admin_jurusan_room_create_defaults_to_own_jurusan(): void
    {
        $gedungId = $this->seedGedung();
        [$elektroId, $mesinId] = $this->seedJurusans();
        $this->actingAsRole('Admin Jurusan', $elektroId);

        $roomId = $this->postJson('/api/v2/ruangan', [
            'gedung_id' => $gedungId,
            'ruangan' => 'C-301',
            'kapasitas' => 40,
            'jurusan_ids' => [$mesinId],
        ])
            ->assertCreated()
            ->assertJsonPath('jurusan_ids', [$elektroId])
            ->json('id');

        $this->assertDatabaseHas('jurusan_ruangan', ['jurusan_id' => $elektroId, 'ruangan_id' => $roomId]);
        $this->assertDatabaseMissing('jurusan_ruangan', ['jurusan_id' => $mesinId, 'ruangan_id' => $roomId]);
    }

    private function actingAsRole(string $role, ?int $jurusanId = null): void
    {
        $roleId = (int) DB::table('role')->insertGetId(['role' => $role]);
        Sanctum::actingAs(UserModel::factory()->create([
            'role_id' => $roleId,
            'jurusan_id' => $jurusanId,
        ]));
    }

    private function seedGedung(): int
    {
        return (int) DB::table('gedung')->insertGetId(['nama_gedung' => 'Gedung C']);
    }

    /**
     * @return array{0:int, 1:int}
     */
    private function seedJurusans(): array
    {
        return [
            (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => 'Teknik Elektro']),
            (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => 'Teknik Mesin']),
        ];
    }
}
