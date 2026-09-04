<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class JurusanRoomAssignmentBuilder
{
    public function __construct(
        private readonly JurusanRoomInitialAllocationBuilder $initialAllocations = new JurusanRoomInitialAllocationBuilder(),
        private readonly JurusanRoomPriorityOrder $priorityOrder = new JurusanRoomPriorityOrder(),
        private readonly JurusanVariableRoomDistributor $distributor = new JurusanVariableRoomDistributor(),
        private readonly AllocatedRoomSorter $sorter = new AllocatedRoomSorter(),
    ) {
    }

    public function assign(
        array $stats,
        Collection $variableRooms,
        ?int $kimiaJurusanId,
        Collection $kimiaFixedRooms,
        array $variableQuota
    ): array {
        $allocations = $this->initialAllocations->build($stats, $kimiaJurusanId, $kimiaFixedRooms, $variableQuota);
        $this->distributor->distribute($allocations, $this->priorityOrder->forStats($stats), $variableRooms->values(), $variableQuota);

        return $this->sorter->sort($allocations);
    }
}
