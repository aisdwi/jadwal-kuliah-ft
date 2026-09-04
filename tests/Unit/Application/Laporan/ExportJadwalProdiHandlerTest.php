<?php

namespace Tests\Unit\Application\Laporan;

use App\Modules\Laporan\Application\Port\ExcelExporterPort;
use App\Modules\Laporan\Application\Service\LaporanAppService;
use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use PHPUnit\Framework\TestCase;

class ExportJadwalProdiHandlerTest extends TestCase
{
    public function test_export_jadwal_returns_file_metadata_and_content(): void
    {
        $jadwalRepo = new FakeJadwalRepository();
        $jadwalRepo->jadwals = [(object) ['id' => 1], (object) ['id' => 2]];
        $prodiRepo = new FakeProgramStudiRepository();
        $prodiRepo->programStudi[5] = (object) ['id' => 5, 'nama_program_studi' => 'Teknik Informatika'];
        $exporter = new FakeExcelExporter();
        $service = new LaporanAppService($jadwalRepo, $prodiRepo, $exporter);

        $result = $service->exportJadwal(5, 3);

        $this->assertSame('xlsx-content', $result['fileContent']);
        $this->assertSame('Jadwal_Teknik_Informatika_Semester3.xlsx', $result['fileName']);
        $this->assertSame(2, $result['jumlahJadwal']);
        $this->assertSame('Teknik Informatika', $result['programStudiNama']);
        $this->assertSame([$jadwalRepo->jadwals, 'Teknik Informatika', 3], $exporter->calls[0]);
    }

    public function test_export_jadwal_uses_fallback_name_when_program_studi_missing(): void
    {
        $service = new LaporanAppService(
            new FakeJadwalRepository(),
            new FakeProgramStudiRepository(),
            new FakeExcelExporter(),
        );

        $result = $service->exportJadwal(999);

        $this->assertSame('Jadwal_Prodi999.xlsx', $result['fileName']);
        $this->assertNull($result['programStudiNama']);
        $this->assertNull($result['semester']);
    }

    public function test_file_name_sanitizes_special_characters(): void
    {
        $prodiRepo = new FakeProgramStudiRepository();
        $prodiRepo->programStudi[1] = (object) ['id' => 1, 'namaProdi' => 'Teknik Informatika & Komputer!'];
        $service = new LaporanAppService(
            new FakeJadwalRepository(),
            $prodiRepo,
            new FakeExcelExporter(),
        );

        $result = $service->exportJadwal(1, 1);

        $this->assertSame('Jadwal_Teknik_Informatika___Komputer__Semester1.xlsx', $result['fileName']);
    }
}

class FakeJadwalRepository implements JadwalRepository
{
    public array $jadwals = [];

    public function findAll(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array
    {
        return $this->jadwals;
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
        return $this->jadwals;
    }

    public function findAssignmentsBySlot(int $slotId, ?int $ignoreKelasKuliahId = null): array
    {
        return [];
    }

    public function create(array $data)
    {
        return (object) $data;
    }

    public function update(int $id, array $data)
    {
        return (object) array_merge(['id' => $id], $data);
    }

    public function delete(int $id): void {}

    public function deleteByKelasKuliah(int $kelasKuliahId): void {}

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
}

class FakeProgramStudiRepository implements ProgramStudiRepository
{
    public array $programStudi = [];

    public function all(?string $search = null, ?int $jurusanId = null, int|string $perPage = 'all')
    {
        return collect($this->programStudi);
    }

    public function findById(int $id)
    {
        return $this->programStudi[$id] ?? null;
    }

    public function findAccessibleById(int $id)
    {
        return $this->findById($id);
    }

    public function create(array $data)
    {
        return (object) $data;
    }

    public function update(int $id, array $data)
    {
        return (object) array_merge(['id' => $id], $data);
    }

    public function delete(int $id): void {}
}

class FakeExcelExporter implements ExcelExporterPort
{
    public array $calls = [];

    public function exportJadwal(array $jadwals, ?string $programStudiNama = null, ?int $semester = null): string
    {
        $this->calls[] = [$jadwals, $programStudiNama, $semester];

        return 'xlsx-content';
    }
}
