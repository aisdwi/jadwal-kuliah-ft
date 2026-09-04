<?php

namespace App\Modules\KelasKuliah\Domain\Entities;

final class KelasKuliahOffering
{
    public function __construct(
        public readonly int $kelasId,
        public readonly int $mataKuliahId,
        public readonly int $dosenId,
        public readonly int $jumlahMahasiswa = 0,
    ) {}

    public function hasSameTeachingAssignment(self $other): bool
    {
        return $this->kelasId === $other->kelasId
            && $this->mataKuliahId === $other->mataKuliahId
            && $this->dosenId === $other->dosenId;
    }

    public function teachingAssignmentKey(): string
    {
        return implode(':', [$this->kelasId, $this->mataKuliahId, $this->dosenId]);
    }

}
