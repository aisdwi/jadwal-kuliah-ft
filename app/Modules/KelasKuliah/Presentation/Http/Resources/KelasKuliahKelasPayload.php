<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahKelasPayload
{
    public static function from(?object $kelas): ?array
    {
        if (!$kelas) {
            return null;
        }

        return [
            'id' => $kelas->id,
            'nama_kelas' => $kelas->nama_kelas,
            'semester' => $kelas->semester,
        ];
    }
}
