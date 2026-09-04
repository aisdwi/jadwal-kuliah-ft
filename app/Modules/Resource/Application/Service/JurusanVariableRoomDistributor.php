<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class JurusanVariableRoomDistributor
{
    public function distribute(array &$allocations, array $priorityOrder, Collection $roomQueue, array $variableQuota): void
    {
        $remainingQuota = $variableQuota;

        while ($roomQueue->isNotEmpty()) {
            if (! $this->distributeRound($allocations, $priorityOrder, $roomQueue, $remainingQuota)) {
                break;
            }
        }
    }

    private function distributeRound(array &$allocations, array $priorityOrder, Collection $roomQueue, array &$remainingQuota): bool
    {
        $assignedInRound = false;

        foreach ($priorityOrder as $jurusanId) {
            if (($remainingQuota[$jurusanId] ?? 0) <= 0) {
                continue;
            }

            $assignedInRound = $this->assignNextRoom($allocations, $jurusanId, $roomQueue, $remainingQuota);

            if ($roomQueue->isEmpty()) {
                return $assignedInRound;
            }
        }

        return $assignedInRound;
    }

    private function assignNextRoom(array &$allocations, int $jurusanId, Collection $roomQueue, array &$remainingQuota): bool
    {
        $room = $roomQueue->shift();
        if ($room === null) {
            return false;
        }

        $allocations[$jurusanId]['rooms'][] = AllocationRoomMapper::map($room);
        $remainingQuota[$jurusanId]--;

        return true;
    }
}
