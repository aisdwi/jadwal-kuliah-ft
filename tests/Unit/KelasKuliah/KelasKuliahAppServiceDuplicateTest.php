<?php

namespace Tests\Unit\KelasKuliah;

use App\Modules\KelasKuliah\Application\Service\KelasKuliahAppService;
use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahStatsRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use App\Modules\Shared\Domain\PagedResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KelasKuliahAppServiceDuplicateTest extends TestCase
{
    public function test_rejects_duplicate_teaching_assignment_before_persisting(): void
    {
        $kelasKuliahRepository = new class implements KelasKuliahRepository {
            public bool $created = false;

            public function all(array $filters = []): PagedResult
            {
                return new PagedResult([]);
            }

            public function findById(int $id)
            {
                return null;
            }

            public function findByKelas(int $kelasId): array
            {
                return [];
            }

            public function findOfferingsByTeachingAssignment(int $kelasId, int $mataKuliahId, int $dosenId, ?int $ignoreId = null): array
            {
                return [new KelasKuliahOffering($kelasId, $mataKuliahId, $dosenId, 25)];
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
        };

        $service = new KelasKuliahAppService(
            $kelasKuliahRepository,
            new class implements KelasKuliahStatsRepository {
                public function stats(?string $semesterTipe = null, ?int $programStudiId = null, ?int $jurusanId = null): array
                {
                    return [];
                }
            },
            new class implements KelasRepository {
                public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): PagedResult
                {
                    return new PagedResult([]);
                }

                public function findById(int $id)
                {
                    return (object) ['id' => $id];
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
                    return (object) $data;
                }

                public function delete(int $id): void
                {
                }
            },
            new class implements MataKuliahRepository {
                public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, ?string $semesterTipe = null, bool $filterByJurusan = true, int|string $perPage = 'all')
                {
                    return [];
                }

                public function findById(int $id)
                {
                    return (object) ['id' => $id];
                }

                public function findAccessibleById(int $id)
                {
                    return (object) ['id' => $id];
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
            },
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Kelas kuliah dengan kelas, mata kuliah, dan dosen yang sama sudah ada.');

        try {
            $service->persist([
                'kelas_id' => 1,
                'matakuliah_id' => 2,
                'dosen_id' => 3,
                'jumlah_mahasiswa' => 40,
            ]);
        } finally {
            $this->assertFalse($kelasKuliahRepository->created);
        }
    }
}
