<?php

namespace Tests\Unit\Resource;

use App\Modules\MasterAkademik\Domain\Repositories\JurusanRepository;
use App\Modules\Resource\Application\Service\AllocationRoomGroups;
use App\Modules\Resource\Application\Service\JurusanRoomAssignmentBuilder;
use App\Modules\Resource\Application\Service\JurusanRoomDemandStatsBuilder;
use App\Modules\Resource\Application\Service\JurusanRuanganAllocationService;
use App\Modules\Resource\Domain\Repositories\JurusanRuanganAllocationRepository;
use App\Modules\Resource\Domain\Repositories\RuanganRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class JurusanRoomAllocationTest extends TestCase
{
    public function test_builds_and_applies_room_recommendation_for_integrated_fixed_and_variable_rooms(): void
    {
        $jurusanRepository = new FakeAllocationJurusanRepository(collect([
            (object) ['id' => 2, 'nama_jurusan' => 'Teknik Informatika'],
            (object) ['id' => 1, 'nama_jurusan' => 'Teknik Kimia'],
        ]));
        $ruanganRepository = new FakeAllocationRuanganRepository(collect([
            $this->room(1, 'INT-01', 80, 'Integrated Classroom'),
            $this->room(2, 'TPK-01', 40, 'Gedung TPK'),
            $this->room(3, 'VAR-02', 30, 'Gedung Utama'),
            $this->room(4, 'VAR-01', 50, 'Gedung Utama'),
        ]));
        $allocationRepository = new FakeJurusanRuanganAllocationRepository(collect([
            1 => (object) [
                'total_classes' => 2,
                'total_sks' => 4,
                'total_students' => 40,
                'avg_students' => 20,
                'large_classes' => 0,
                'medium_classes' => 1,
            ],
            2 => (object) [
                'total_classes' => 4,
                'total_sks' => 12,
                'total_students' => 160,
                'avg_students' => 40,
                'large_classes' => 1,
                'medium_classes' => 2,
            ],
        ]));

        $service = new JurusanRuanganAllocationService(
            $jurusanRepository,
            $ruanganRepository,
            $allocationRepository,
        );

        $report = $service->applyRecommendation();

        $this->assertSame(1, $report['meta']['integrated_room_count']);
        $this->assertSame(1, $report['meta']['kimia_fixed_room_count']);
        $this->assertSame(2, $report['meta']['variable_room_count']);
        $this->assertSame(3, $report['meta']['allocatable_room_count']);
        $this->assertSame('TPK-01', $report['allocations'][1]['rooms'][0]['ruangan']);
        $this->assertSame(4, $report['allocations'][2]['rooms'][0]['id']);
        $this->assertSame(3, $report['allocations'][2]['rooms'][1]['id']);
        $this->assertCount(3, $allocationRepository->replacedRows);
    }

    public function test_groups_rooms_and_assigns_none_when_no_variable_rooms_are_available(): void
    {
        $groups = AllocationRoomGroups::from(collect([
            $this->room(1, 'INT-01', 80, 'Integrated Classroom'),
            $this->room(2, 'PETRO-01', 40, 'Gedung Petrokimia'),
        ]));

        $allocations = (new JurusanRoomAssignmentBuilder())->assign(
            stats: [
                1 => [
                    'jurusan_id' => 1,
                    'nama_jurusan' => 'Teknik Kimia',
                    'total_classes' => 1,
                    'avg_students' => 20,
                ],
                2 => [
                    'jurusan_id' => 2,
                    'nama_jurusan' => 'Teknik Informatika',
                    'total_classes' => 0,
                    'avg_students' => 0,
                ],
            ],
            variableRooms: $groups->variableRooms,
            kimiaJurusanId: 1,
            kimiaFixedRooms: $groups->kimiaFixedRooms,
            variableQuota: [1 => 0, 2 => 0],
        );

        $this->assertSame(1, $groups->allocatableRoomCount());
        $this->assertSame('PETRO-01', $groups->roomPayload()['kimia_fixed'][0]['ruangan']);
        $this->assertSame(1, $allocations[1]['total_room_count']);
        $this->assertSame(0, $allocations[2]['total_room_count']);
    }

    public function test_demand_stats_use_zero_defaults_for_department_without_demand(): void
    {
        $stats = (new JurusanRoomDemandStatsBuilder())->build(
            collect([(object) ['id' => 9, 'nama_jurusan' => 'Teknik Tanpa Kelas']]),
            collect(),
            null,
            0,
            0,
        );

        $this->assertSame(0, $stats[9]['total_classes']);
        $this->assertSame(0, $stats[9]['class_share']);
        $this->assertSame(0, $stats[9]['variable_demand']);
    }

    private function room(int $id, string $name, int $capacity, string $building): object
    {
        return (object) [
            'id' => $id,
            'ruangan' => $name,
            'kapasitas' => $capacity,
            'gedung' => (object) ['nama_gedung' => $building],
        ];
    }
}

class FakeAllocationJurusanRepository implements JurusanRepository
{
    public function __construct(private readonly Collection $jurusans) {}

    public function all(?string $search = null, int|string $perPage = 'all'): Collection
    {
        return $this->jurusans;
    }

    public function findById(int $id): mixed
    {
        return null;
    }

    public function create(array $data): mixed
    {
        return null;
    }

    public function update(int $id, array $data): mixed
    {
        return null;
    }

    public function delete(int $id): void {}
}

class FakeAllocationRuanganRepository implements RuanganRepository
{
    public function __construct(private readonly Collection $rooms) {}

    public function all(?string $search = null, ?int $jurusanId = null): Collection
    {
        return $this->rooms;
    }

    public function allForAllocation(): Collection
    {
        return $this->rooms;
    }

    public function findById(int $id): mixed
    {
        return null;
    }

    public function create(array $data): mixed
    {
        return null;
    }

    public function update(int $id, array $data): mixed
    {
        return null;
    }

    public function delete(int $id): void {}
}

class FakeJurusanRuanganAllocationRepository implements JurusanRuanganAllocationRepository
{
    public array $replacedRows = [];

    public function __construct(private readonly Collection $demands) {}

    public function loadJurusanDemands(): Collection
    {
        return $this->demands;
    }

    public function replaceAllocations(array $rows): void
    {
        $this->replacedRows = $rows;
    }
}
