<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class QueuedSchedulingProgressPayload
{
    private const LOG_TIME_FORMAT = 'H:i:s';
    private const ALL_DATA_LABEL = 'Semua Data';

    public static function make(array $params, array $scope): array
    {
        return [
            'status' => 'queued',
            'generation' => 0,
            'max_generation' => $params['max_generation'],
            'best_fitness' => 0,
            'logs' => [[
                'type' => 'info',
                'message' => 'Job penjadwalan dimasukkan ke antrean background.',
                'timestamp' => now()->format(self::LOG_TIME_FORMAT),
            ]],
            'result' => null,
            'started_at' => now()->toISOString(),
            'scope_label' => $scope['label'] ?? self::ALL_DATA_LABEL,
            'message' => 'Menunggu worker memulai proses generate jadwal.',
        ];
    }
}
