<?php

namespace App\Modules\Resource\Application\Service;

final class AllocationRoomPayload
{
    public static function from(AllocationRoomGroups $groups): array
    {
        return [
            'integrated' => $groups->integratedRooms->map(fn (object $room) => AllocationRoomMapper::map($room))->all(),
            'kimia_fixed' => $groups->kimiaFixedRooms->map(fn (object $room) => AllocationRoomMapper::map($room))->all(),
            'variable' => $groups->variableRooms->map(fn (object $room) => AllocationRoomMapper::map($room))->all(),
        ];
    }
}
