<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class AllocationRoomClassifier
{
    public function __construct(private readonly Collection $rooms) {}

    public function integratedRooms(): Collection
    {
        return $this->rooms
            ->filter(fn (object $room): bool => $this->isIntegratedRoom($room))
            ->values();
    }

    public function kimiaFixedRooms(): Collection
    {
        return $this->rooms
            ->filter(fn (object $room): bool => $this->isKimiaFixedRoom($room))
            ->values();
    }

    public function variableRooms(Collection $integratedRooms, Collection $kimiaFixedRooms): Collection
    {
        $excludedIds = array_merge($integratedRooms->pluck('id')->all(), $kimiaFixedRooms->pluck('id')->all());

        return $this->rooms
            ->reject(fn (object $room): bool => in_array($room->id, $excludedIds, true))
            ->sortBy([
                ['kapasitas', 'desc'],
                ['ruangan', 'asc'],
            ])
            ->values();
    }

    private function isIntegratedRoom(object $room): bool
    {
        return str_contains(strtolower((string) $room->gedung?->nama_gedung), 'integrated');
    }

    private function isKimiaFixedRoom(object $room): bool
    {
        return in_array((string) $room->gedung?->nama_gedung, ['Gedung TPK', 'Gedung Petrokimia'], true);
    }
}
