<?php

namespace Tests\Feature\KelasKuliah;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaprodiKelasKuliahAccessTest extends TestCase
{
    public function test_kaprodi_can_update_own_program_studi_kelas_kuliah(): void
    {
        $fixture = $this->seedFixture();
        $this->actingAsKaprodi($fixture['program_studi_id']);

        $this->putJson('/api/v2/kelas-kuliah/' . $fixture['kelas_kuliah_id'], [
            'dosen_id' => $fixture['dosen_id'],
            'matakuliah_id' => $fixture['matakuliah_id'],
            'kelas_id' => $fixture['kelas_id'],
            'jumlah_mahasiswa' => 38,
            'dosen_team' => [
                [
                    'dosen_id' => $fixture['dosen_id'],
                    'preferred_slot_id' => $fixture['slot_id'],
                    'is_external' => false,
                ],
                [
                    'dosen_id' => $fixture['second_dosen_id'],
                    'preferred_slot_id' => null,
                    'is_external' => true,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('jumlah_mahasiswa', 38)
            ->assertJsonPath('dosens.0.id', $fixture['dosen_id'])
            ->assertJsonPath('dosens.0.pivot.preferred_slot_id', $fixture['slot_id'])
            ->assertJsonPath('dosens.0.pivot.is_external', false)
            ->assertJsonPath('dosens.1.id', $fixture['second_dosen_id'])
            ->assertJsonPath('dosens.1.pivot.preferred_slot_id', null)
            ->assertJsonPath('dosens.1.pivot.is_external', true);

        $this->assertDatabaseHas('kelas_kuliah', [
            'id' => $fixture['kelas_kuliah_id'],
            'jumlah_mahasiswa' => 38,
        ]);
        $this->assertDatabaseHas('kelas_kuliah_dosen', [
            'kelas_kuliah_id' => $fixture['kelas_kuliah_id'],
            'dosen_id' => $fixture['dosen_id'],
            'preferred_slot_id' => $fixture['slot_id'],
            'is_external' => 0,
        ]);
        $this->assertDatabaseHas('kelas_kuliah_dosen', [
            'kelas_kuliah_id' => $fixture['kelas_kuliah_id'],
            'dosen_id' => $fixture['second_dosen_id'],
            'preferred_slot_id' => null,
            'is_external' => 1,
        ]);
    }

    public function test_kaprodi_cannot_update_other_program_studi_kelas_kuliah(): void
    {
        $own = $this->seedFixture('Teknik Elektro', 'S1 Elektro', 'EL');
        $other = $this->seedFixture('Teknik Mesin', 'S1 Mesin', 'MS');
        $this->actingAsKaprodi($own['program_studi_id']);

        $this->putJson('/api/v2/kelas-kuliah/' . $other['kelas_kuliah_id'], [
            'dosen_id' => $other['dosen_id'],
            'matakuliah_id' => $other['matakuliah_id'],
            'kelas_id' => $other['kelas_id'],
            'jumlah_mahasiswa' => 41,
        ])->assertNotFound();
    }

    public function test_kaprodi_only_receives_own_program_studi_reference_options(): void
    {
        $own = $this->seedFixture('Teknik Elektro', 'S1 Elektro', 'EL');
        $this->seedFixture('Teknik Elektro', 'D3 Elektro', 'D3E', $own['jurusan_id']);
        $this->actingAsKaprodi($own['program_studi_id']);

        $this->getJson('/api/v2/referensi/program-studi')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $own['program_studi_id'])
            ->assertJsonPath('0.nama_prodi', 'S1 Elektro');
    }

    public function test_kaprodi_kelas_kuliah_list_is_scoped_to_own_program_studi_even_in_same_jurusan(): void
    {
        $own = $this->seedFixture('Teknik Elektro', 'S1 Elektro', 'EL');
        $other = $this->seedFixture('Teknik Elektro', 'D3 Elektro', 'D3E', $own['jurusan_id']);
        $this->actingAsKaprodi($own['program_studi_id']);

        $response = $this->getJson('/api/v2/kelas-kuliah?per_page=all')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($own['kelas_kuliah_id'], $ids);
        $this->assertNotContains($other['kelas_kuliah_id'], $ids);
    }

    public function test_kelas_kuliah_response_falls_back_to_primary_dosen_when_team_pivot_is_empty(): void
    {
        $fixture = $this->seedFixture();
        $this->actingAsKaprodi($fixture['program_studi_id']);

        $this->getJson('/api/v2/kelas-kuliah/' . $fixture['kelas_kuliah_id'])
            ->assertOk()
            ->assertJsonPath('dosen.id', $fixture['dosen_id'])
            ->assertJsonPath('dosens.0.id', $fixture['dosen_id'])
            ->assertJsonPath('dosens.0.pivot.preferred_slot_id', null)
            ->assertJsonPath('dosens.0.pivot.is_external', false);
    }

    private function actingAsKaprodi(int $programStudiId): void
    {
        DB::table('role')->updateOrInsert(['id' => 7], ['role' => 'Koordinator Program Studi']);
        Sanctum::actingAs(UserModel::factory()->create([
            'role_id' => 7,
            'program_studi_id' => $programStudiId,
        ]));
    }

    private function seedFixture(
        string $jurusanName = 'Teknik Pengujian',
        string $programStudiName = 'S1 Pengujian',
        string $prefix = 'TST',
        ?int $existingJurusanId = null,
    ): array {
        $jurusanId = $existingJurusanId ?? (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => $jurusanName]);
        $programStudiId = (int) DB::table('program_studi')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nama_prodi' => $programStudiName,
        ]);
        $dosenId = (int) DB::table('dosen')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nip' => $prefix . '001',
            'nama_lengkap' => 'Dosen ' . $prefix,
            'inisial' => $prefix,
        ]);
        $secondDosenId = (int) DB::table('dosen')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nip' => $prefix . '002',
            'nama_lengkap' => 'Dosen Kedua ' . $prefix,
            'inisial' => $prefix . '2',
        ]);
        $matakuliahId = (int) DB::table('matakuliah')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'kode_mk' => $prefix . '101',
            'nama_mk' => 'Mata Kuliah ' . $prefix,
            'sks' => 2,
            'semester' => 1,
        ]);
        $kelasId = (int) DB::table('kelas')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'nama_kelas' => $prefix . '-A',
            'semester' => 1,
        ]);
        $kelasKuliahId = (int) DB::table('kelas_kuliah')->insertGetId([
            'dosen_id' => $dosenId,
            'matakuliah_id' => $matakuliahId,
            'kelas_id' => $kelasId,
            'jumlah_mahasiswa' => 30,
        ]);
        $hariId = (int) DB::table('hari')->insertGetId(['nama_hari' => 'Senin ' . $prefix]);
        $waktuId = (int) DB::table('waktu')->insertGetId([
            'pukul' => '08.00 - 09.40 ' . $prefix,
            'sks' => 2,
            'jam_index' => 1,
        ]);
        $slotId = (int) DB::table('slot')->insertGetId([
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
        ]);

        return compact('jurusanId', 'programStudiId', 'dosenId', 'matakuliahId', 'kelasId', 'kelasKuliahId') + [
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'kelas_kuliah_id' => $kelasKuliahId,
            'matakuliah_id' => $matakuliahId,
            'kelas_id' => $kelasId,
            'dosen_id' => $dosenId,
            'second_dosen_id' => $secondDosenId,
            'slot_id' => $slotId,
        ];
    }
}
