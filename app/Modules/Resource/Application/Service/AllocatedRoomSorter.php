<?php

namespace App\Modules\Resource\Application\Service;

final class AllocatedRoomSorter
{
    public function sort(array $allocations): array
    {
        foreach ($allocations as $jurusanId => $allocation) {
            usort($allocation['rooms'], fn (array $left, array $right): int => $this->compare($left, $right));
            $allocations[$jurusanId]['rooms'] = $allocation['rooms'];
            $allocations[$jurusanId]['total_room_count'] = count($allocation['rooms']);
        }

        return $allocations;
    }

    private function compare(array $left, array $right): int
    {
        $buildingCompare = strcmp($left['gedung'], $right['gedung']);
        if ($buildingCompare !== 0) {
            return $buildingCompare;
        }

        if ($left['kapasitas'] !== $right['kapasitas']) {
            return $right['kapasitas'] <=> $left['kapasitas'];
        }

        return strcmp($left['ruangan'], $right['ruangan']);
    }
}
