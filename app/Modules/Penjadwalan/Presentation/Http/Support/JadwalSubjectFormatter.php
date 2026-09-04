<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

use App\Modules\Penjadwalan\Presentation\Http\Resources\JadwalResource;

final class JadwalSubjectFormatter
{
    public static function format(mixed $jadwal): string
    {
        $data = JadwalResource::toArray($jadwal);
        $label = JadwalCourseLabel::from($data);
        $slotLabel = JadwalSlotLabel::from($data);

        if ($label !== '' && $slotLabel !== '') {
            return "{$label} ({$slotLabel})";
        }

        return self::fallbackLabel($label, $slotLabel);
    }

    private static function fallbackLabel(string $label, string $slotLabel): string
    {
        $resolved = $label === '' ? $slotLabel : $label;

        return $resolved !== '' ? $resolved : 'Jadwal';
    }
}
