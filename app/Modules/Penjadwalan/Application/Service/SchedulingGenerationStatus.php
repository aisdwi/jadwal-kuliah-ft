<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class SchedulingGenerationStatus
{
    public static function fromResult(array $result): string
    {
        if ($result['canceled'] ?? false) {
            return 'canceled';
        }

        return ($result['success'] ?? false) ? 'completed' : 'failed';
    }
}
