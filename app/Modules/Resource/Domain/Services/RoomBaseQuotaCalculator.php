<?php

namespace App\Modules\Resource\Domain\Services;

final class RoomBaseQuotaCalculator
{
    public function calculate(array $stats, array $weights, float $weightSum, int $availableRooms): array
    {
        $quotas = [];
        $remainders = [];
        $usedRooms = 0;

        foreach ($stats as $jurusanId => $_) {
            $row = $this->quotaRow((float) $weights[$jurusanId], $weightSum, $availableRooms);
            $quotas[$jurusanId] = $row['quota'];
            $remainders[$jurusanId] = $row['remainder'];
            $usedRooms += $row['quota'];
        }

        return [$quotas, $remainders, $usedRooms];
    }

    /**
     * @return array{quota: int, remainder: float}
     */
    private function quotaRow(float $weight, float $weightSum, int $availableRooms): array
    {
        $rawQuota = ($weight / $weightSum) * $availableRooms;
        $baseQuota = (int) floor($rawQuota);

        return [
            'quota' => $baseQuota,
            'remainder' => $rawQuota - $baseQuota,
        ];
    }
}
