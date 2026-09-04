<?php

namespace Tests\Unit\Application\KelasKuliah;

use App\Modules\KelasKuliah\Application\Service\KelasKuliahAppService;
use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahStatsRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use App\Modules\Shared\Domain\PagedResult;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KelasKuliahAppServiceTest extends TestCase
{
    private FakeKelasKuliahRepository $repo;
    private FakeKelasKuliahStatsRepository $statsRepo;
    private FakeKelasRepository $kelasRepo;
    private FakeMataKuliahRepository $mataKuliahRepo;
    private KelasKuliahAppService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new FakeKelasKuliahRepository();
        $this->statsRepo = new FakeKelasKuliahStatsRepository();
        $this->kelasRepo = new FakeKelasRepository();
        $this->mataKuliahRepo = new FakeMataKuliahRepository();
        $this->service = new KelasKuliahAppService(
            $this->repo,
            $this->statsRepo,
            $this->kelasRepo,
            $this->mataKuliahRepo,
        );
    }

    public function test_list_delegates_filters_to_repository(): void
    {
        $this->repo->allResult = new PagedResult([(object) ['id' => 1]], 1, 1, 10);

        $filters = [
            'program_studi_id' => 5,
            'jurusan_id' => 2,
            'semester' => 3,
            'semester_tipe' => 'ganjil',
            'dosen_id' => 9,
            'search' => 'Algoritma',
            'per_page' => 10,
            'is_scheduled' => true,
        ];

        $result = $this->service->list($filters);

        $this->assertSame(1, $result->total);
        $this->assertSame($filters, $this->repo->allCalls[0]);
    }

    public function test_find_by_id_returns_repository_result(): void
    {
        $this->repo->items[7] = (object) ['id' => 7, 'kelas_id' => 2];

        $this->assertSame(7, $this->service->findById(7)->id);
    }

    public function test_find_by_kelas_returns_repository_items(): void
    {
        $this->repo->byKelas[3] = [(object) ['id' => 1], (object) ['id' => 2]];

        $this->assertCount(2, $this->service->findByKelas(3));
    }

    public function test_stats_delegates_to_stats_repository(): void
    {
        $this->statsRepo->result = ['total' => 3];

        $this->assertSame(['total' => 3], $this->service->stats('genap', 8, 2));
        $this->assertSame(['genap', 8, 2], $this->statsRepo->calls[0]);
    }

    public function test_persist_creates_kelas_kuliah_when_references_are_valid(): void
    {
        $this->kelasRepo->items[4] = (object) ['id' => 4];
        $this->mataKuliahRepo->items[10] = (object) ['id' => 10];

        $result = $this->service->persist([
            'kelas_id' => 4,
            'matakuliah_id' => 10,
            'dosen_id' => 6,
            'jumlah_mahasiswa' => 35,
            'ignored' => 'value',
        ]);

        $this->assertSame(1, $result->id);
        $this->assertEquals([
            'dosen_id' => 6,
            'matakuliah_id' => 10,
            'kelas_id' => 4,
            'jumlah_mahasiswa' => 35,
        ], $this->repo->createdPayload);
    }

    public function test_persist_rejects_duplicate_teaching_assignment(): void
    {
        $this->kelasRepo->items[4] = (object) ['id' => 4];
        $this->mataKuliahRepo->items[10] = (object) ['id' => 10];
        $this->repo->offerings = [new KelasKuliahOffering(4, 10, 6, 30)];

        $this->expectException(HttpException::class);

        $this->service->persist([
            'kelas_id' => 4,
            'matakuliah_id' => 10,
            'dosen_id' => 6,
            'jumlah_mahasiswa' => 35,
        ]);
    }

    public function test_delete_checks_existence_before_delete(): void
    {
        $this->repo->items[9] = (object) ['id' => 9];

        $this->service->delete(9);

        $this->assertSame(9, $this->repo->deletedId);
    }
}

class FakeKelasKuliahRepository implements KelasKuliahRepository
{
    public PagedResult $allResult;
    public array $allCalls = [];
    public array $items = [];
    public array $byKelas = [];
    public array $offerings = [];
    public array $createdPayload = [];
    public array $updatedPayload = [];
    public ?int $deletedId = null;

    public function __construct()
    {
        $this->allResult = new PagedResult([]);
    }

    public function all(array $filters = []): PagedResult
    {
        $this->allCalls[] = $filters;

        return $this->allResult;
    }

    public function findById(int $id)
    {
        return $this->items[$id] ?? null;
    }

    public function findByKelas(int $kelasId): array
    {
        return $this->byKelas[$kelasId] ?? [];
    }

    public function findOfferingsByTeachingAssignment(int $kelasId, int $mataKuliahId, int $dosenId, ?int $ignoreId = null): array
    {
        return $this->offerings;
    }

    public function create(array $data)
    {
        $this->createdPayload = $data;

        return (object) array_merge(['id' => 1], $data);
    }

    public function update(int $id, array $data)
    {
        $this->updatedPayload = $data;

        return (object) array_merge(['id' => $id], $data);
    }

    public function delete(int $id): void
    {
        $this->deletedId = $id;
    }
}

class FakeKelasKuliahStatsRepository implements KelasKuliahStatsRepository
{
    public array $result = [];
    public array $calls = [];

    public function stats(?string $semesterTipe = null, ?int $programStudiId = null, ?int $jurusanId = null): array
    {
        $this->calls[] = [$semesterTipe, $programStudiId, $jurusanId];

        return $this->result;
    }
}

class FakeKelasRepository implements KelasRepository
{
    public array $items = [];

    public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): PagedResult
    {
        return new PagedResult([]);
    }

    public function findById(int $id)
    {
        return $this->items[$id] ?? null;
    }

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array
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
}

class FakeMataKuliahRepository implements MataKuliahRepository
{
    public array $items = [];

    public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, ?string $semesterTipe = null, bool $filterByJurusan = true, int|string $perPage = 'all')
    {
        return collect();
    }

    public function findById(int $id)
    {
        return $this->items[$id] ?? null;
    }

    public function findAccessibleById(int $id)
    {
        return $this->items[$id] ?? null;
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
