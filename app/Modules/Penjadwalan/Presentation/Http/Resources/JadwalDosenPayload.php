<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalDosenPayload
{
    public static function from(?array $dosen): ?array
    {
        return $dosen ? [
            'id' => $dosen['id'] ?? null,
            'nama_lengkap' => $dosen['nama_lengkap'] ?? null,
        ] : null;
    }
}
