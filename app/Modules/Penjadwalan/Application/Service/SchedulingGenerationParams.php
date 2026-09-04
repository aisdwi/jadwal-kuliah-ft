<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class SchedulingGenerationParams
{
    public static function normalize(array $params): array
    {
        return [
            'num_kromosom' => max(10, min(300, (int) ($params['num_kromosom'] ?? 45))),
            'max_generation' => max(10, min(500, (int) ($params['max_generation'] ?? 60))),
            'crossover_rate' => max(10, min(100, (int) ($params['crossover_rate'] ?? 85))),
            'mutation_rate' => max(1, min(100, (int) ($params['mutation_rate'] ?? 40))),
        ];
    }
}
