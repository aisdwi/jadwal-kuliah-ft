<?php

namespace Tests\Feature;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Penjadwalan\Presentation\Http\Support\JadwalSubjectFormatter;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JadwalControllerCoverageTest extends TestCase
{
    public function test_super_admin_can_assign_reassign_clear_generate_and_delete_schedule(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedSchedulingFixture();

        $jadwalId = $this->putJson('/api/v2/kelas-kuliah/1/jadwal', [
            'slot_id' => 1,
            'ruangan_id' => 1,
        ])->assertCreated()->json('id');

        $this->putJson("/api/v2/jadwal/{$jadwalId}/reassign", [
            'kelas_kuliah_id' => 1,
            'slot_id' => 2,
            'ruangan_id' => 2,
            'origin' => 'manual',
        ])->assertOk()->assertJsonPath('slot_id', 2);

        $this->postJson('/api/v2/jadwal/generate')
            ->assertStatus(422)
            ->assertJsonPath('message', 'program_studi_id diperlukan');
        $this->postJson('/api/v2/jadwal/generate', [
            'program_studi_id' => 1,
            'semester' => 1,
        ])->assertOk()->assertJsonPath('message', 'Jadwal sudah dihapus');

        $this->deleteJson("/api/v2/jadwal/{$jadwalId}")->assertNoContent();

        $this->putJson('/api/v2/kelas-kuliah/1/jadwal', [
            'slot_id' => 1,
            'ruangan_id' => 1,
        ])->assertCreated();
        $this->putJson('/api/v2/kelas-kuliah/1/jadwal')
            ->assertOk()
            ->assertJsonPath('message', 'Jadwal berhasil dihapus');

        $this->assertDatabaseCount('jadwal', 0);
        $this->assertDatabaseCount('activity_logs', 6);
    }

    public function test_subject_formatter_uses_course_slot_and_fallback_labels(): void
    {
        $this->assertSame('CVG101 - Coverage Scheduling / CVG-A (Senin, 08.00 - 09.40, CVG-101)', JadwalSubjectFormatter::format([
            'kelas_kuliah' => [
                'matakuliah' => ['kode_mk' => 'CVG101', 'nama_mk' => 'Coverage Scheduling'],
                'kelas' => ['nama_kelas' => 'CVG-A'],
            ],
            'slot' => [
                'hari' => ['nama_hari' => 'Senin'],
                'waktu' => ['pukul' => '08.00 - 09.40'],
            ],
            'ruangan' => ['ruangan' => 'CVG-101'],
        ]));
        $this->assertSame('Jadwal', JadwalSubjectFormatter::format([]));
    }

    private function actingAsSuperAdmin(): void
    {
        DB::table('role')->updateOrInsert(['id' => 999], ['role' => 'Super Admin']);
        Sanctum::actingAs(UserModel::factory()->create(['role_id' => 999]));
    }

    private function seedSchedulingFixture(): void
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2, 'jam_index' => 1],
            ['id' => 2, 'pukul' => '09.50 - 11.30', 'sks' => 2, 'jam_index' => 2],
        ]);
        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1],
            ['id' => 2, 'hari_id' => 1, 'waktu_id' => 2],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung Coverage']);
        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'CVG-101', 'kapasitas' => 40],
            ['id' => 2, 'gedung_id' => 1, 'ruangan' => 'CVG-102', 'kapasitas' => 50],
        ]);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Coverage']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Coverage']);
        DB::table('dosen')->insert([
            'id' => 1,
            'jurusan_id' => 1,
            'nip' => '199001012026061001',
            'nama_lengkap' => 'Dosen Coverage',
            'inisial' => 'DC',
        ]);
        DB::table('matakuliah')->insert([
            'id' => 1,
            'program_studi_id' => 1,
            'jurusan_id' => 1,
            'kode_mk' => 'CVG101',
            'nama_mk' => 'Coverage Scheduling',
            'sks' => 2,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert(['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'CVG-A', 'semester' => 1]);
        DB::table('kelas_kuliah')->insert([
            'id' => 1,
            'dosen_id' => 1,
            'matakuliah_id' => 1,
            'kelas_id' => 1,
            'jumlah_mahasiswa' => 30,
            'semester_tipe' => 'ganjil',
        ]);
    }
}
