<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class FailedSchedulingProgressPayload
{
    private const LOG_TIME_FORMAT = 'H:i:s';
    private const ALL_DATA_LABEL = 'Semua Data';

    public static function make(array $previous, array $scope, string $message, string $startedAt): array
    {
        return [
            'status' => 'failed',
            'generation' => (int) ($previous['generation'] ?? 0),
            'max_generation' => (int) ($previous['max_generation'] ?? 0),
            'best_fitness' => (float) ($previous['best_fitness'] ?? 0),
            'logs' => array_merge($previous['logs'] ?? [], [[
                'type' => 'error',
                'message' => $message,
                'timestamp' => now()->format(self::LOG_TIME_FORMAT),
            ]]),
            'result' => null,
            'started_at' => $previous['started_at'] ?? $startedAt,
            'scope_label' => $scope['label'] ?? ($previous['scope_label'] ?? self::ALL_DATA_LABEL),
            'message' => $message,
        ];
    }
}
