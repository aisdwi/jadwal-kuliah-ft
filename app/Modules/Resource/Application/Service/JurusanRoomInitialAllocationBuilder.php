<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class JurusanRoomInitialAllocationBuilder
{
    public function build(array $stats, ?int $kimiaJurusanId, Collection $kimiaFixedRooms, array $variableQuota): array
    {
        $allocations = [];

        foreach ($stats as $jurusanId => $stat) {
            $allocations[$jurusanId] = $this->allocationRow($jurusanId, $stat, $kimiaJurusanId, $kimiaFixedRooms, $variableQuota);
        }

        return $allocations;
    }

    private function allocationRow(int $jurusanId, array $stat, ?int $kimiaJurusanId, Collection $kimiaFixedRooms, array $variableQuota): array
    {
        $fixedRooms = $this->fixedRooms($jurusanId, $kimiaJurusanId, $kimiaFixedRooms);

        return [
            'jurusan_id' => $jurusanId,
            'nama_jurusan' => $stat['nama_jurusan'],
            'fixed_room_count' => count($fixedRooms),
            'variable_room_count' => (int) ($variableQuota[$jurusanId] ?? 0),
            'rooms' => $fixedRooms,
        ];
    }

    private function fixedRooms(int $jurusanId, ?int $kimiaJurusanId, Collection $kimiaFixedRooms): array
    {
        if ($jurusanId !== $kimiaJurusanId) {
            return [];
        }

        return $kimiaFixedRooms
            ->map(fn (object $room): array => AllocationRoomMapper::map($room))
            ->all();
    }
}
