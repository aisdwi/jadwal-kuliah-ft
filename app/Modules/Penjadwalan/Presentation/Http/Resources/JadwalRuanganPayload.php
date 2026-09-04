<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalRuanganPayload
{
    public static function from(?array $ruangan): ?array
    {
        return $ruangan ? [
            'id' => $ruangan['id'] ?? null,
            'ruangan' => $ruangan['ruangan'] ?? null,
            'kapasitas' => $ruangan['kapasitas'] ?? null,
        ] : null;
    }
}
