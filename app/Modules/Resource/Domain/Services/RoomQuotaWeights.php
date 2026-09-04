<?php

namespace App\Modules\Resource\Domain\Services;

final class RoomQuotaWeights
{
    public function zeroQuota(array $stats): array
    {
        $quotas = [];
        foreach ($stats as $jurusanId => $_) {
            $quotas[$jurusanId] = 0;
        }

        return $quotas;
    }

    public function resolved(array $stats): array
    {
        $weights = $this->demandWeights($stats);

        return array_sum($weights) > 0
            ? $weights
            : $this->equalWeightsForActiveJurusans($stats);
    }

    private function equalWeightsForActiveJurusans(array $stats): array
    {
        $activeJurusanIds = array_keys(array_filter(
            $stats,
            fn (array $stat): bool => ($stat['total_classes'] ?? 0) > 0,
        ));

        if ($activeJurusanIds === []) {
            return $this->zeroQuota($stats);
        }

        return $this->equalWeights($stats, $activeJurusanIds);
    }

    private function equalWeights(array $stats, array $activeJurusanIds): array
    {
        $equalWeight = 1 / count($activeJurusanIds);
        $weights = [];

        foreach ($stats as $jurusanId => $_) {
            $weights[$jurusanId] = in_array($jurusanId, $activeJurusanIds, true) ? $equalWeight : 0;
        }

        return $weights;
    }

    private function demandWeights(array $stats): array
    {
        $weights = [];
        foreach ($stats as $jurusanId => $stat) {
            $weights[$jurusanId] = max(0, (float) $stat['variable_demand']);
        }

        return $weights;
    }
}
