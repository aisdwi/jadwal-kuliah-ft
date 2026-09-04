<?php

namespace Tests\Feature;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataCrudSmokeTest extends TestCase
{
    public function test_jurusan_crud_endpoint(): void
    {
        $this->actingAsSuperAdmin();

        $id = $this->postJson('/api/v2/jurusan', ['nama_jurusan' => 'Teknik Pengujian'])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/jurusan/{$id}", ['nama_jurusan' => 'Teknik Pengujian Update'])
            ->assertOk()
            ->assertJsonPath('nama_jurusan', 'Teknik Pengujian Update');

        $this->deleteJson("/api/v2/jurusan/{$id}")->assertNoContent();
    }

    public function test_program_studi_crud_endpoint(): void
    {
        $this->actingAsSuperAdmin();
        $jurusanId = $this->seedJurusan();

        $id = $this->postJson('/api/v2/program-studi', [
            'nama_prodi' => 'S1 Pengujian',
            'jurusan_id' => $jurusanId,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/program-studi/{$id}", [
            'nama_prodi' => 'S1 Pengujian Update',
            'jurusan_id' => $jurusanId,
        ])
            ->assertOk()
            ->assertJsonPath('nama_prodi', 'S1 Pengujian Update');

        $this->deleteJson("/api/v2/program-studi/{$id}")->assertNoContent();
    }

    public function test_dosen_crud_endpoint(): void
    {
        $this->actingAsSuperAdmin();
        $jurusanId = $this->seedJurusan();

        $id = $this->postJson('/api/v2/dosen', [
            'nip' => '198001012026051001',
            'nama_lengkap' => 'Dosen Pengujian',
            'inisial' => 'DP',
            'jurusan_id' => $jurusanId,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/dosen/{$id}", [
            'nip' => '198001012026051002',
            'nama_lengkap' => 'Dosen Pengujian Update',
            'inisial' => 'DPU',
            'jurusan_id' => $jurusanId,
        ])
            ->assertOk()
            ->assertJsonPath('nama_lengkap', 'Dosen Pengujian Update');

        $this->deleteJson("/api/v2/dosen/{$id}")->assertNoContent();
    }

    public function test_mata_kuliah_and_kelas_crud_endpoints(): void
    {
        $this->actingAsSuperAdmin();
        $jurusanId = $this->seedJurusan();
        $programStudiId = $this->seedProgramStudi($jurusanId);

        $mataKuliahId = $this->postJson('/api/v2/matakuliah', [
            'kode_mk' => 'TST101',
            'nama_mk' => 'Mata Kuliah Pengujian',
            'sks' => 2,
            'semester' => 1,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/matakuliah/{$mataKuliahId}", [
            'kode_mk' => 'TST102',
            'nama_mk' => 'Mata Kuliah Pengujian Update',
            'sks' => 3,
            'semester' => 1,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
        ])
            ->assertOk()
            ->assertJsonPath('nama_mk', 'Mata Kuliah Pengujian Update');

        $kelasId = $this->postJson('/api/v2/kelas', [
            'nama_kelas' => 'TST-A',
            'semester' => 1,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/kelas/{$kelasId}", [
            'nama_kelas' => 'TST-B',
            'semester' => 1,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
        ])
            ->assertOk()
            ->assertJsonPath('nama_kelas', 'TST-B');

        $this->deleteJson("/api/v2/kelas/{$kelasId}")->assertNoContent();
        $this->deleteJson("/api/v2/matakuliah/{$mataKuliahId}")->assertNoContent();
    }

    public function test_resource_crud_endpoints_for_hari_waktu_ruangan_and_slot(): void
    {
        $this->actingAsSuperAdmin();
        $jurusanId = $this->seedJurusan();
        $gedungId = DB::table('gedung')->insertGetId(['nama_gedung' => 'Gedung Test']);

        $hariId = $this->postJson('/api/v2/hari', ['nama_hari' => 'Sabtu'])
            ->assertCreated()
            ->json('id');
        $this->putJson("/api/v2/hari/{$hariId}", ['nama_hari' => 'Minggu'])->assertOk();

        $waktuId = $this->postJson('/api/v2/waktu', ['pukul' => '07.00 - 08.40', 'sks' => 2])
            ->assertCreated()
            ->json('id');
        $this->putJson("/api/v2/waktu/{$waktuId}", ['pukul' => '08.50 - 10.30', 'sks' => 2])->assertOk();

        $ruanganId = $this->postJson('/api/v2/ruangan', [
            'gedung_id' => $gedungId,
            'ruangan' => 'T-101',
            'kapasitas' => 40,
        ])
            ->assertCreated()
            ->json('id');
        $this->putJson("/api/v2/ruangan/{$ruanganId}", [
            'gedung_id' => $gedungId,
            'ruangan' => 'T-102',
            'kapasitas' => 45,
        ])->assertOk();

        $slotId = $this->postJson('/api/v2/slot', [
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
            'jurusan_id' => $jurusanId,
        ])
            ->assertCreated()
            ->json('id');
        $this->putJson("/api/v2/slot/{$slotId}", [
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
            'jurusan_id' => $jurusanId,
        ])->assertOk();

        $this->deleteJson("/api/v2/slot/{$slotId}")->assertNoContent();
        $this->deleteJson("/api/v2/ruangan/{$ruanganId}")->assertNoContent();
        $this->deleteJson("/api/v2/waktu/{$waktuId}")->assertNoContent();
        $this->deleteJson("/api/v2/hari/{$hariId}")->assertNoContent();
    }

    public function test_kelas_kuliah_and_user_crud_endpoints(): void
    {
        $this->actingAsSuperAdmin();
        $jurusanId = $this->seedJurusan();
        $programStudiId = $this->seedProgramStudi($jurusanId);
        $dosenId = $this->seedDosen($jurusanId);
        $mataKuliahId = $this->seedMataKuliah($jurusanId, $programStudiId);
        $kelasId = $this->seedKelas($jurusanId, $programStudiId);

        $kelasKuliahId = $this->postJson('/api/v2/kelas-kuliah', [
            'dosen_id' => $dosenId,
            'matakuliah_id' => $mataKuliahId,
            'kelas_id' => $kelasId,
            'jumlah_mahasiswa' => 25,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/kelas-kuliah/{$kelasKuliahId}", [
            'dosen_id' => $dosenId,
            'matakuliah_id' => $mataKuliahId,
            'kelas_id' => $kelasId,
            'jumlah_mahasiswa' => 30,
        ])
            ->assertOk()
            ->assertJsonPath('jumlah_mahasiswa', 30);

        $this->deleteJson("/api/v2/kelas-kuliah/{$kelasKuliahId}")->assertNoContent();

        $userId = $this->postJson('/api/v2/users', [
            'nama_user' => 'User Pengujian',
            'email' => 'user-pengujian@example.test',
            'password' => 'password123',
            'role_id' => 6,
            'jurusan_id' => $jurusanId,
        ])
            ->assertCreated()
            ->json('id');

        $this->putJson("/api/v2/users/{$userId}", [
            'nama_user' => 'User Pengujian Update',
            'email' => 'user-pengujian-update@example.test',
            'password' => 'password123',
            'role_id' => 6,
            'jurusan_id' => $jurusanId,
        ])
            ->assertOk()
            ->assertJsonPath('nama_user', 'User Pengujian Update');

        $this->deleteJson("/api/v2/users/{$userId}")->assertNoContent();
    }

    private function actingAsSuperAdmin(): void
    {
        DB::table('role')->updateOrInsert(['id' => 1], ['role' => 'Super Admin']);
        DB::table('role')->updateOrInsert(['id' => 6], ['role' => 'Admin Jurusan']);
        Sanctum::actingAs(UserModel::factory()->create(['role_id' => 1]));
    }

    private function seedJurusan(): int
    {
        return (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => 'Teknik Pengujian']);
    }

    private function seedProgramStudi(int $jurusanId): int
    {
        return (int) DB::table('program_studi')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nama_prodi' => 'S1 Pengujian',
        ]);
    }

    private function seedDosen(int $jurusanId): int
    {
        return (int) DB::table('dosen')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nip' => '199001012026051001',
            'nama_lengkap' => 'Dosen Smoke',
            'inisial' => 'DS',
        ]);
    }

    private function seedMataKuliah(int $jurusanId, int $programStudiId): int
    {
        return (int) DB::table('matakuliah')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'kode_mk' => 'SMK101',
            'nama_mk' => 'Smoke Mata Kuliah',
            'sks' => 2,
            'semester' => 1,
        ]);
    }

    private function seedKelas(int $jurusanId, int $programStudiId): int
    {
        return (int) DB::table('kelas')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'nama_kelas' => 'SMK-A',
            'semester' => 1,
        ]);
    }
}
