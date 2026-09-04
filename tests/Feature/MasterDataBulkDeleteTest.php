<?php

namespace Tests\Feature;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataBulkDeleteTest extends TestCase
{
    public function test_super_admin_can_delete_all_dosen_from_collection_endpoint(): void
    {
        $this->actingAsRole('Super Admin');
        $jurusanId = $this->seedJurusan('Teknik Bulk');
        $dosenIds = [
            $this->seedDosen($jurusanId, 'BULK-001', 'Dosen Bulk 1'),
            $this->seedDosen($jurusanId, 'BULK-002', 'Dosen Bulk 2'),
        ];

        $this->deleteJson('/api/v2/dosen')
            ->assertOk()
            ->assertJsonPath('deleted_count', 2);

        foreach ($dosenIds as $dosenId) {
            $this->assertDatabaseMissing('dosen', ['id' => $dosenId]);
        }
    }

    public function test_admin_jurusan_bulk_delete_only_removes_ruangan_in_own_scope(): void
    {
        [$elektroId, $mesinId] = [
            $this->seedJurusan('Teknik Elektro Bulk'),
            $this->seedJurusan('Teknik Mesin Bulk'),
        ];
        $gedungId = (int) DB::table('gedung')->insertGetId(['nama_gedung' => 'Gedung Bulk']);
        $ownRoomId = $this->seedRuangan($gedungId, 'BULK-E-101', $elektroId);
        $otherRoomId = $this->seedRuangan($gedungId, 'BULK-M-101', $mesinId);

        $this->actingAsRole('Admin Jurusan', $elektroId);

        $this->deleteJson('/api/v2/ruangan')
            ->assertOk()
            ->assertJsonPath('deleted_count', 1);

        $this->assertDatabaseMissing('ruangan', ['id' => $ownRoomId]);
        $this->assertDatabaseHas('ruangan', ['id' => $otherRoomId]);
    }

    private function actingAsRole(string $role, ?int $jurusanId = null): void
    {
        $roleId = (int) DB::table('role')->insertGetId(['role' => $role]);
        Sanctum::actingAs(UserModel::factory()->create([
            'role_id' => $roleId,
            'jurusan_id' => $jurusanId,
        ]));
    }

    private function seedJurusan(string $name): int
    {
        return (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => $name]);
    }

    private function seedDosen(int $jurusanId, string $nip, string $name): int
    {
        return (int) DB::table('dosen')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nip' => $nip,
            'nama_lengkap' => $name,
            'inisial' => substr($nip, -2),
        ]);
    }

    private function seedRuangan(int $gedungId, string $name, int $jurusanId): int
    {
        $roomId = (int) DB::table('ruangan')->insertGetId([
            'gedung_id' => $gedungId,
            'ruangan' => $name,
            'kapasitas' => 40,
        ]);
        DB::table('jurusan_ruangan')->insert([
            'jurusan_id' => $jurusanId,
            'ruangan_id' => $roomId,
        ]);

        return $roomId;
    }
}
