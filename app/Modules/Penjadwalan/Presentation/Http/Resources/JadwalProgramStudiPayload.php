<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalProgramStudiPayload
{
    public static function from(?array $programStudi): ?array
    {
        return $programStudi ? [
            'id' => $programStudi['id'] ?? null,
            'nama_prodi' => $programStudi['nama_prodi'] ?? null,
        ] : null;
    }
}
