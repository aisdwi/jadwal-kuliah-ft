<?php

namespace App\Modules\Penjadwalan\Domain\Entities;

final class ScheduleConflict
{
    public function __construct(
        public readonly string $type,
        public readonly ScheduleAssignment $assignment,
    ) {}

    public function message(): string
    {
        return match ($this->type) {
            'ruangan' => 'Ruangan sudah digunakan pada slot waktu yang sama.',
            'kelas' => 'Kelas sudah memiliki jadwal pada slot waktu yang sama.',
            'dosen' => 'Dosen pengampu sudah memiliki jadwal pada slot waktu yang sama.',
            default => 'Jadwal bentrok dengan jadwal lain.',
        };
    }
}
