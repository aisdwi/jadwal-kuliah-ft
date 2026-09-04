<?php

namespace App\Modules\Resource\Domain\Services;

final class RoomAllocationPolicy
{
    public function __construct(
        private readonly RoomQuotaWeights $weights = new RoomQuotaWeights(),
        private readonly RoomBaseQuotaCalculator $baseQuotas = new RoomBaseQuotaCalculator(),
        private readonly RoomLeftoverDistributor $leftovers = new RoomLeftoverDistributor(),
    ) {
    }

    public function allocateVariableRoomQuota(array $stats, int $availableRooms): array
    {
        $quotas = $this->weights->zeroQuota($stats);

        if ($availableRooms <= 0) {
            return $quotas;
        }

        $weights = $this->weights->resolved($stats);
        $weightSum = array_sum($weights);
        if ($weightSum <= 0) {
            return $quotas;
        }

        [$quotas, $remainders, $usedRooms] = $this->baseQuotas->calculate($stats, $weights, $weightSum, $availableRooms);

        return $this->leftovers->distribute($quotas, $remainders, $stats, $availableRooms - $usedRooms);
    }
}
