<?php

namespace App\Modules\Resource\Application\Service;

final class JurusanRoomPriorityOrder
{
    public function forStats(array $stats): array
    {
        $priorityOrder = array_keys(array_filter($stats, fn (array $stat): bool => $stat['total_classes'] > 0));
        usort($priorityOrder, fn (int $left, int $right): int => $this->compare($left, $right, $stats));

        return $priorityOrder;
    }

    private function compare(int $left, int $right, array $stats): int
    {
        foreach (['avg_students', 'total_classes'] as $criterion) {
            $comparison = $stats[$right][$criterion] <=> $stats[$left][$criterion];
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return $stats[$left]['jurusan_id'] <=> $stats[$right]['jurusan_id'];
    }
}
