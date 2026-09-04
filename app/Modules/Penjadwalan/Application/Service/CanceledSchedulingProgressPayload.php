<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class CanceledSchedulingProgressPayload
{
    private const LOG_TIME_FORMAT = 'H:i:s';
    private const ALL_DATA_LABEL = 'Semua Data';

    public static function make(array $previous): array
    {
        return [
            'status' => 'canceled',
            'generation' => (int) ($previous['generation'] ?? 0),
            'max_generation' => (int) ($previous['max_generation'] ?? 0),
            'best_fitness' => (float) ($previous['best_fitness'] ?? 0),
            'logs' => array_merge($previous['logs'] ?? [], [[
                'type' => 'info',
                'message' => 'Permintaan pembatalan dikirim. Worker akan berhenti pada pengecekan berikutnya.',
                'timestamp' => now()->format(self::LOG_TIME_FORMAT),
            ]]),
            'result' => $previous['result'] ?? null,
            'started_at' => $previous['started_at'] ?? now()->toISOString(),
            'scope_label' => $previous['scope_label'] ?? self::ALL_DATA_LABEL,
            'message' => 'Generate jadwal dibatalkan.',
        ];
    }
}
