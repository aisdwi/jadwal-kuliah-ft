<?php

namespace App\Modules\Penjadwalan\Domain\Entities;

final class ScheduleAssignment
{
    /**
     * @param list<int> $lecturerIds
     */
    public function __construct(
        public readonly int $kelasKuliahId,
        public readonly int $kelasId,
        public readonly int $slotId,
        public readonly int $ruanganId,
        public readonly array $lecturerIds = [],
        public readonly ?int $dayId = null,
        public readonly ?int $startMinute = null,
        public readonly ?int $endMinute = null,
    ) {}

    public function isSameCourseOffering(self $other): bool
    {
        return $this->kelasKuliahId === $other->kelasKuliahId;
    }

    public function isSameSlot(self $other): bool
    {
        return $this->slotId === $other->slotId;
    }

    public function overlapsTimeWith(self $other): bool
    {
        if (!$this->hasTimeRange() || !$other->hasTimeRange()) {
            return $this->isSameSlot($other);
        }

        return $this->dayId === $other->dayId
            && $this->startMinute < $other->endMinute
            && $other->startMinute < $this->endMinute;
    }

    public function usesSameRoom(self $other): bool
    {
        return $this->ruanganId === $other->ruanganId;
    }

    public function usesSameClassGroup(self $other): bool
    {
        return $this->kelasId === $other->kelasId;
    }

    public function sharesLecturerWith(self $other): bool
    {
        return $this->sharedLecturerIds($other) !== [];
    }

    private function hasTimeRange(): bool
    {
        return $this->dayId !== null
            && $this->startMinute !== null
            && $this->endMinute !== null;
    }

    /**
     * @return list<int>
     */
    public function sharedLecturerIds(self $other): array
    {
        $shared = array_values(array_intersect($this->lecturerIds, $other->lecturerIds));

        return array_map('intval', $shared);
    }
}
