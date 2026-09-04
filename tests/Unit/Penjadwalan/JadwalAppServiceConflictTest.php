<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories\EloquentKelasKuliahRepository;
use App\Modules\Penjadwalan\Application\Service\JadwalAppService;
use App\Modules\Penjadwalan\Domain\Entities\ScheduleAssignment;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories\EloquentJadwalRepository;
use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class JadwalAppServiceConflictTest extends TestCase
{
    public function test_rejects_room_conflict_when_time_ranges_overlap_on_different_slots(): void
    {
        $this->seedOverlappingScheduleFixture();

        $service = new JadwalAppService(
            new EloquentJadwalRepository(new JadwalModel()),
            new EloquentKelasKuliahRepository(new KelasKuliahModel()),
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Ruangan sudah digunakan pada slot waktu yang sama.');

        $service->persist([
            'kelas_kuliah_id' => 2,
            'slot_id' => 2,
            'ruangan_id' => 3,
        ]);
    }

    public function test_rejects_schedule_assignment_when_room_is_already_used_on_same_slot(): void
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert(['id' => 7, 'pukul' => '08.00-09.40', 'sks' => 2]);
        DB::table('slot')->insert(['id' => 7, 'hari_id' => 1, 'waktu_id' => 7]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung A']);
        DB::table('ruangan')->insert(['id' => 3, 'gedung_id' => 1, 'ruangan' => 'A101', 'kapasitas' => 40]);

        $jadwalRepository = new class implements JadwalRepository {
            public bool $created = false;

            public function findAll(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array
            {
                return [];
            }

            public function findById(int $id)
            {
                return null;
            }

            public function findByKelasKuliah(int $kelasKuliahId)
            {
                return null;
            }

            public function findByProgramStudi(int $programStudiId, ?int $semester = null): array
            {
                return [];
            }

            public function findAssignmentsBySlot(int $slotId, ?int $ignoreKelasKuliahId = null): array
            {
                return [new ScheduleAssignment(2, 11, $slotId, 3, [101])];
            }

            public function create(array $data)
            {
                $this->created = true;
                return (object) $data;
            }

            public function update(int $id, array $data)
            {
                return (object) $data;
            }

            public function delete(int $id): void
            {
            }

            public function deleteByKelasKuliah(int $kelasKuliahId): void
            {
            }

            public function deleteAll(?int $programStudiId = null): int
            {
                return 0;
            }

            public function deleteGeneratedByProgramStudi(int $programStudiId, ?int $semester = null): int
            {
                return 0;
            }

            public function findGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): array
            {
                return [];
            }

            public function deleteGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): int
            {
                return 0;
            }

            public function restoreGeneratedSchedules(array $schedules): int
            {
                return count($schedules);
            }
        };

        $kelasKuliahRepository = new class implements KelasKuliahRepository {
            public function all(array $filters = []): PagedResult
            {
                return new PagedResult([]);
            }

            public function findById(int $id)
            {
                return (object) [
                    'id' => $id,
                    'kelas_id' => 10,
                    'dosen_id' => 100,
                    'dosens' => [],
                ];
            }

            public function findByKelas(int $kelasId): array
            {
                return [];
            }

            public function findOfferingsByTeachingAssignment(int $kelasId, int $mataKuliahId, int $dosenId, ?int $ignoreId = null): array
            {
                return [];
            }

            public function create(array $data)
            {
                return (object) $data;
            }

            public function update(int $id, array $data)
            {
                return (object) $data;
            }

            public function delete(int $id): void
            {
            }
        };

        $service = new JadwalAppService($jadwalRepository, $kelasKuliahRepository);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Ruangan sudah digunakan pada slot waktu yang sama.');

        try {
            $service->persist([
                'kelas_kuliah_id' => 1,
                'slot_id' => 7,
                'ruangan_id' => 3,
            ]);
        } finally {
            $this->assertFalse($jadwalRepository->created);
        }
    }

    private function seedOverlappingScheduleFixture(): void
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2],
            ['id' => 2, 'pukul' => '08.00 - 10.30', 'sks' => 3],
        ]);
        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1],
            ['id' => 2, 'hari_id' => 1, 'waktu_id' => 2],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung A']);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'D3 Teknik Sipil']);
        DB::table('ruangan')->insert(['id' => 3, 'gedung_id' => 1, 'ruangan' => 'C-306', 'kapasitas' => 40]);
        DB::table('dosen')->insert([
            ['id' => 100, 'jurusan_id' => 1, 'nama_lengkap' => 'Dosen A'],
            ['id' => 101, 'jurusan_id' => 1, 'nama_lengkap' => 'Dosen B'],
        ]);
        DB::table('matakuliah')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS101', 'nama_mk' => 'Fisika Dasar', 'sks' => 2, 'semester' => 1],
            ['id' => 2, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS102', 'nama_mk' => 'Matematika Dasar', 'sks' => 3, 'semester' => 1],
        ]);
        DB::table('kelas')->insert([
            ['id' => 10, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'TS-A', 'semester' => 1],
            ['id' => 11, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'TS-B', 'semester' => 1],
        ]);
        DB::table('kelas_kuliah')->insert([
            ['id' => 1, 'dosen_id' => 100, 'matakuliah_id' => 1, 'kelas_id' => 10, 'jumlah_mahasiswa' => 30],
            ['id' => 2, 'dosen_id' => 101, 'matakuliah_id' => 2, 'kelas_id' => 11, 'jumlah_mahasiswa' => 30],
        ]);
        DB::table('jadwal')->insert([
            'id' => 1,
            'kelas_kuliah_id' => 1,
            'slot_id' => 1,
            'ruangan_id' => 3,
            'origin' => 'manual',
        ]);
    }
}
