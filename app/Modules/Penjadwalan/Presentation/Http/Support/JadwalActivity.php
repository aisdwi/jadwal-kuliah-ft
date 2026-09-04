<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

use App\Modules\Shared\Application\Service\ActivityLogger;

final class JadwalActivity
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function log(string $action, mixed $jadwal, string $verb): void
    {
        $subjectName = JadwalSubjectFormatter::format($jadwal);
        $this->logger->log(
            action: $action,
            subjectType: 'Jadwal',
            subjectName: $subjectName,
            description: "{$verb} {$subjectName}",
        );
    }

    public function logClearedManualAssignment(int $kelasKuliahId): void
    {
        $this->logger->log(
            action: 'delete',
            subjectType: 'Jadwal',
            subjectName: 'Kelas kuliah #' . $kelasKuliahId,
            description: 'Mengosongkan jadwal kelas kuliah #' . $kelasKuliahId,
        );
    }

    public function logGeneratedDeletion(int $programStudiId, mixed $semester): void
    {
        $this->logger->log(
            action: 'delete',
            subjectType: 'Jadwal',
            subjectName: $semester ? 'Semester ' . $semester : 'Program studi #' . $programStudiId,
            description: 'Menghapus jadwal generate untuk program studi #' . $programStudiId,
        );
    }
}
