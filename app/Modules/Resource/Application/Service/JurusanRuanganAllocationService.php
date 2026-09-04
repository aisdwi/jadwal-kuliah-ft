<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\MasterAkademik\Domain\Repositories\JurusanRepository;
use App\Modules\Resource\Domain\Repositories\JurusanRuanganAllocationRepository;
use App\Modules\Resource\Domain\Repositories\RuanganRepository;
use App\Modules\Resource\Domain\Services\RoomAllocationPolicy;

class JurusanRuanganAllocationService
{
    public function __construct(
        private readonly JurusanRepository $jurusanRepo,
        private readonly RuanganRepository $ruanganRepo,
        private readonly JurusanRuanganAllocationRepository $allocationRepo,
        private ?RoomAllocationPolicy $allocationPolicy = null,
    ) {}

    public function buildRecommendation(): array
    {
        $jurusans = $this->jurusanRepo->all()->sortBy('id')->values();
        $kimiaJurusan = $jurusans->firstWhere('nama_jurusan', 'Teknik Kimia');
        $kimiaJurusanId = $kimiaJurusan?->id;
        $roomGroups = AllocationRoomGroups::from($this->ruanganRepo->allForAllocation());
        $stats = $this->statsBuilder()->build(
            $jurusans,
            $this->allocationRepo->loadJurusanDemands(),
            $kimiaJurusanId,
            $roomGroups->allocatableRoomCount(),
            $roomGroups->kimiaFixedRooms->count(),
        );
        $variableQuota = $this->allocationPolicy()->allocateVariableRoomQuota($stats, $roomGroups->variableRooms->count());

        return [
            'meta' => [
                'allocation_basis' => 'all_non_mku_classes_ignore_current_schedule_positions',
                'integrated_room_count' => $roomGroups->integratedRooms->count(),
                'kimia_fixed_room_count' => $roomGroups->kimiaFixedRooms->count(),
                'variable_room_count' => $roomGroups->variableRooms->count(),
                'allocatable_room_count' => $roomGroups->allocatableRoomCount(),
            ],
            'stats' => $stats,
            'rooms' => $roomGroups->roomPayload(),
            'allocations' => $this->assignmentBuilder()->assign($stats, $roomGroups->variableRooms, $kimiaJurusanId, $roomGroups->kimiaFixedRooms, $variableQuota),
        ];
    }

    public function applyRecommendation(): array
    {
        $report = $this->buildRecommendation();
        $rows = [];

        foreach ($report['allocations'] as $jurusanId => $allocation) {
            foreach ($allocation['rooms'] as $room) {
                $rows[] = [
                    'jurusan_id' => $jurusanId,
                    'ruangan_id' => $room['id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $this->allocationRepo->replaceAllocations($rows);

        return $report;
    }

    private function allocationPolicy(): RoomAllocationPolicy
    {
        return $this->allocationPolicy ??= new RoomAllocationPolicy();
    }

    private function statsBuilder(): JurusanRoomDemandStatsBuilder
    {
        return new JurusanRoomDemandStatsBuilder();
    }

    private function assignmentBuilder(): JurusanRoomAssignmentBuilder
    {
        return new JurusanRoomAssignmentBuilder();
    }
}
