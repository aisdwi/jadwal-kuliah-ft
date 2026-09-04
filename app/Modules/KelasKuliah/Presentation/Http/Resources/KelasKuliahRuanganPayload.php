<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahRuanganPayload
{
    public static function from(?object $ruangan): ?array
    {
        if (!$ruangan) {
            return null;
        }

        return [
            'id' => $ruangan->id,
            'ruangan' => $ruangan->ruangan,
            'kapasitas' => $ruangan->kapasitas,
        ];
    }
}
