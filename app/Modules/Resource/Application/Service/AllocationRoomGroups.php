<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class AllocationRoomGroups
{
    private function __construct(
        public readonly Collection $integratedRooms,
        public readonly Collection $kimiaFixedRooms,
        public readonly Collection $variableRooms,
    ) {}

    public static function from(Collection $rooms): self
    {
        $classifier = new AllocationRoomClassifier($rooms);
        $integratedRooms = $classifier->integratedRooms();
        $kimiaFixedRooms = $classifier->kimiaFixedRooms();

        return new self(
            integratedRooms: $integratedRooms,
            kimiaFixedRooms: $kimiaFixedRooms,
            variableRooms: $classifier->variableRooms($integratedRooms, $kimiaFixedRooms),
        );
    }

    public function allocatableRoomCount(): int
    {
        return $this->kimiaFixedRooms->count() + $this->variableRooms->count();
    }

    public function roomPayload(): array
    {
        return AllocationRoomPayload::from($this);
    }
}
