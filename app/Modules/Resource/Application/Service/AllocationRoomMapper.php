<?php

namespace App\Modules\Resource\Application\Service;

final class AllocationRoomMapper
{
    public static function map(object $room): array
    {
        return [
            'id' => $room->id,
            'ruangan' => $room->ruangan,
            'kapasitas' => (int) $room->kapasitas,
            'gedung' => (string) $room->gedung?->nama_gedung,
        ];
    }
}
