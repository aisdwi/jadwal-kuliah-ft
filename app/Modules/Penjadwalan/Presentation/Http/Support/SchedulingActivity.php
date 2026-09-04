<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

use App\Modules\Shared\Application\Service\ActivityLogger;

final class SchedulingActivity
{
    private const SUBJECT_TYPE = 'Generate Jadwal';

    public function __construct(private readonly ActivityLogger $logger) {}

    public function log(string $action, string $subjectName, string $description): void
    {
        $this->logger->log(
            action: $action,
            subjectType: self::SUBJECT_TYPE,
            subjectName: $subjectName,
            description: $description,
        );
    }

    public function logGenerate(?string $semesterTipe): void
    {
        $label = SchedulingSemesterLabel::format($semesterTipe);
        $this->log('generate', $label, 'Memulai generate jadwal otomatis ' . $label);
    }
}
