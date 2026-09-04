<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class WaktuReferencePayload
{
    public static function from(mixed $waktu): array
    {
        return [
            'id' => is_array($waktu) ? $waktu['id'] : $waktu->id,
            'pukul' => is_array($waktu) ? $waktu['pukul'] : $waktu->pukul,
            'sks' => is_array($waktu) ? $waktu['sks'] : $waktu->sks,
        ];
    }
}
