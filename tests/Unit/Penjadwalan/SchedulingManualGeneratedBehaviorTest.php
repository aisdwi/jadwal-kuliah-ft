<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\SchedulingGenerationDatasetQuery;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\SchedulingScheduleWriter;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchedulingManualGeneratedBehaviorTest extends TestCase
{
    public function test_generation_dataset_treats_existing_schedules_as_fixed_and_only_targets_unscheduled_classes(): void
    {
        $seed = $this->seedSchedulingRows();
        $manualKelasKuliahId = $seed['kelas_kuliah_ids'][0];
        $generatedKelasKuliahId = $seed['kelas_kuliah_ids'][1];
        $unscheduledKelasKuliahId = $seed['kelas_kuliah_ids'][2];
        $outsideGeneratedKelasKuliahId = $this->seedOutsideJurusanScheduledClass($seed);

        DB::table('jadwal')->insert([
            [
                'kelas_kuliah_id' => $manualKelasKuliahId,
                'slot_id' => $seed['slot_id'],
                'ruangan_id' => $seed['ruangan_id'],
                'origin' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kelas_kuliah_id' => $generatedKelasKuliahId,
                'slot_id' => $seed['slot_id_2'],
                'ruangan_id' => $seed['ruangan_id'],
                'origin' => 'generated',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kelas_kuliah_id' => $outsideGeneratedKelasKuliahId,
                'slot_id' => $seed['slot_id'],
                'ruangan_id' => $seed['ruangan_id_2'],
                'origin' => 'generated',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dataset = (new SchedulingGenerationDatasetQuery())->getDataset([
            'is_restricted' => true,
            'restrict_by_jurusan' => true,
            'jurusan_id' => $seed['jurusan_id'],
        ], null);

        $this->assertEqualsCanonicalizing(
            [$manualKelasKuliahId, $generatedKelasKuliahId, $outsideGeneratedKelasKuliahId],
            $dataset['manual']->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$unscheduledKelasKuliahId],
            $dataset['kuliah']->pluck('id')->all(),
        );
    }

    public function test_generated_assignment_does_not_overwrite_existing_manual_schedule(): void
    {
        $seed = $this->seedSchedulingRows();
        $manualKelasKuliahId = $seed['kelas_kuliah_ids'][0];

        DB::table('jadwal')->insert([
            'kelas_kuliah_id' => $manualKelasKuliahId,
            'slot_id' => $seed['slot_id'],
            'ruangan_id' => $seed['ruangan_id'],
            'origin' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new SchedulingScheduleWriter())->persistGeneratedAssignments([
            [
                'kuliah' => $manualKelasKuliahId,
                'slot' => $seed['slot_id_2'],
                'ruang' => $seed['ruangan_id_2'],
            ],
        ]);

        $this->assertDatabaseHas('jadwal', [
            'kelas_kuliah_id' => $manualKelasKuliahId,
            'slot_id' => $seed['slot_id'],
            'ruangan_id' => $seed['ruangan_id'],
            'origin' => 'manual',
        ]);
        $this->assertDatabaseMissing('jadwal', [
            'kelas_kuliah_id' => $manualKelasKuliahId,
            'slot_id' => $seed['slot_id_2'],
            'ruangan_id' => $seed['ruangan_id_2'],
            'origin' => 'generated',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function seedSchedulingRows(): array
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2],
            ['id' => 2, 'pukul' => '09.50 - 11.30', 'sks' => 2],
        ]);
        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1],
            ['id' => 2, 'hari_id' => 1, 'waktu_id' => 2],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung C']);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Elektro']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Teknik Elektro']);
        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'C-301', 'kapasitas' => 40],
            ['id' => 2, 'gedung_id' => 1, 'ruangan' => 'Vcon', 'kapasitas' => 60],
        ]);
        DB::table('jurusan_slot')->insert([
            ['jurusan_id' => 1, 'slot_id' => 1],
            ['jurusan_id' => 1, 'slot_id' => 2],
        ]);
        DB::table('jurusan_ruangan')->insert([
            ['jurusan_id' => 1, 'ruangan_id' => 1],
            ['jurusan_id' => 1, 'ruangan_id' => 2],
        ]);
        DB::table('dosen')->insert([
            'id' => 1,
            'jurusan_id' => 1,
            'nip' => '198001012026051001',
            'nama_lengkap' => 'Dosen Elektro',
            'inisial' => 'DE',
        ]);
        DB::table('matakuliah')->insert([
            'id' => 1,
            'program_studi_id' => 1,
            'jurusan_id' => 1,
            'kode_mk' => 'TEE101',
            'nama_mk' => 'Rangkaian Listrik',
            'sks' => 2,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert(['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'A', 'semester' => 1]);

        $kelasKuliahRows = [];
        foreach ([1, 2, 3] as $id) {
            $kelasKuliahRows[] = [
                'id' => $id,
                'dosen_id' => 1,
                'matakuliah_id' => 1,
                'kelas_id' => 1,
                'jumlah_mahasiswa' => 25,
            ];
        }
        DB::table('kelas_kuliah')->insert($kelasKuliahRows);

        return [
            'jurusan_id' => 1,
            'slot_id' => 1,
            'slot_id_2' => 2,
            'ruangan_id' => 1,
            'ruangan_id_2' => 2,
            'kelas_kuliah_ids' => [1, 2, 3],
        ];
    }

    private function seedOutsideJurusanScheduledClass(array $seed): int
    {
        DB::table('jurusan')->insert(['id' => 2, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('program_studi')->insert(['id' => 2, 'jurusan_id' => 2, 'nama_prodi' => 'S1 Teknik Sipil']);
        DB::table('dosen')->insert([
            'id' => 2,
            'jurusan_id' => 2,
            'nip' => '198001012026051002',
            'nama_lengkap' => 'Dosen Sipil',
            'inisial' => 'DS',
        ]);
        DB::table('matakuliah')->insert([
            'id' => 2,
            'program_studi_id' => 2,
            'jurusan_id' => 2,
            'kode_mk' => 'TSP101',
            'nama_mk' => 'Struktur Beton',
            'sks' => 2,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert([
            'id' => 2,
            'program_studi_id' => 2,
            'jurusan_id' => 2,
            'nama_kelas' => 'S-A',
            'semester' => 1,
        ]);
        DB::table('kelas_kuliah')->insert([
            'id' => 4,
            'dosen_id' => 2,
            'matakuliah_id' => 2,
            'kelas_id' => 2,
            'jumlah_mahasiswa' => 30,
        ]);
        DB::table('jurusan_slot')->insert([
            ['jurusan_id' => 2, 'slot_id' => $seed['slot_id']],
            ['jurusan_id' => 2, 'slot_id' => $seed['slot_id_2']],
        ]);
        DB::table('jurusan_ruangan')->insert([
            ['jurusan_id' => 2, 'ruangan_id' => $seed['ruangan_id_2']],
        ]);

        return 4;
    }
}
