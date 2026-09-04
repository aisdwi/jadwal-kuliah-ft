<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

final class SchedulingSemesterLabel
{
    public static function format(?string $semesterTipe): string
    {
        return match (strtolower(trim((string) $semesterTipe))) {
            'ganjil' => 'semester ganjil',
            'genap' => 'semester genap',
            default => 'semester aktif',
        };
    }
}
