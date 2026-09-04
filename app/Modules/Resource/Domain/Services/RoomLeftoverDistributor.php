<?php

namespace App\Modules\Resource\Domain\Services;

final class RoomLeftoverDistributor
{
    public function distribute(array $quotas, array $remainders, array $stats, int $leftover): array
    {
        foreach ($this->priority($remainders, $stats) as $jurusanId) {
            if ($leftover <= 0) {
                break;
            }

            if (($stats[$jurusanId]['total_classes'] ?? 0) <= 0) {
                continue;
            }

            $quotas[$jurusanId]++;
            $leftover--;
        }

        return $quotas;
    }

    private function priority(array $remainders, array $stats): array
    {
        $orderedJurusanIds = array_keys($remainders);
        usort($orderedJurusanIds, fn (int $left, int $right): int => $this->compare($left, $right, $remainders, $stats));

        return $orderedJurusanIds;
    }

    private function compare(int $left, int $right, array $remainders, array $stats): int
    {
        foreach (['remainder', 'avg_students', 'total_classes'] as $criterion) {
            $comparison = $this->compareCriterion($criterion, $left, $right, $remainders, $stats);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function compareCriterion(string $criterion, int $left, int $right, array $remainders, array $stats): int
    {
        return $this->criterionValue($criterion, $right, $remainders, $stats)
            <=> $this->criterionValue($criterion, $left, $remainders, $stats);
    }

    private function criterionValue(string $criterion, int $jurusanId, array $remainders, array $stats): float
    {
        if ($criterion === 'remainder') {
            return (float) $remainders[$jurusanId];
        }

        return (float) ($stats[$jurusanId][$criterion] ?? 0);
    }
}
