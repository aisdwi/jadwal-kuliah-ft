<?php

namespace App\Modules\Shared\Application\Service;

use App\Modules\Shared\Application\Port\ActivityLogRepositoryPort;

class ActivityLogger
{
    public function __construct(
        private readonly ActivityLogRepositoryPort $activityLogRepository,
    ) {}

    public function log(
        string $action,
        string $subjectType,
        ?string $subjectName = null,
        ?string $description = null,
        ?int $userId = null,
    ): void {
        $this->activityLogRepository->insert([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_name' => $subjectName,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
