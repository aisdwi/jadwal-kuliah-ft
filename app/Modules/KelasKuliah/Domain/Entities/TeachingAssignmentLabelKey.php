<?php

namespace App\Modules\KelasKuliah\Domain\Entities;

final class TeachingAssignmentLabelKey
{
    public static function from(string $kodeMk, string $kelas, string $dosen): string
    {
        return implode(':', [
            self::normalize($kodeMk),
            self::normalize($kelas),
            self::normalize($dosen),
        ]);
    }

    private static function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }
}
