<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class JadwalAssignmentValue
{
    public static function nullableInt(array $data, string $key): ?int
    {
        return isset($data[$key]) ? (int) $data[$key] : null;
    }
}
