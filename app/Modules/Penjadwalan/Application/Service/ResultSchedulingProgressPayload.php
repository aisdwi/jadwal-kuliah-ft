<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class ResultSchedulingProgressPayload
{
    private const ALL_DATA_LABEL = 'Semua Data';

    public static function make(array $result, array $previous, array $scope, string $status, string $startedAt): array
    {
        return [
            'status' => $status,
            'generation' => (int) SchedulingProgressValue::first([$result, $previous], 'generation', 0),
            'max_generation' => (int) SchedulingProgressValue::firstNested($result, $previous, 'result.max_generation', 'max_generation', 0),
            'best_fitness' => (float) SchedulingProgressValue::first([$result, $result, $previous], ['best_fitness', 'fitness', 'best_fitness'], 0),
            'logs' => SchedulingProgressValue::first([$result, $previous], 'logs', []),
            'result' => SchedulingProgressValue::value($result, 'result'),
            'started_at' => SchedulingProgressValue::value($previous, 'started_at', $startedAt),
            'scope_label' => SchedulingProgressValue::first([$scope, $previous], ['label', 'scope_label'], self::ALL_DATA_LABEL),
            'message' => SchedulingProgressValue::value($result, 'message', self::defaultMessage($status)),
        ];
    }

    private static function defaultMessage(string $status): string
    {
        return match ($status) {
            'completed' => 'Generate jadwal selesai.',
            'canceled' => 'Generate jadwal dibatalkan.',
            default => 'Generate jadwal belum berhasil diselesaikan.',
        };
    }
}
