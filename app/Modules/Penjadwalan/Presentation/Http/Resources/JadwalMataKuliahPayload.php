<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalMataKuliahPayload
{
    public static function from(?array $matakuliah): ?array
    {
        return $matakuliah ? [
            'id' => $matakuliah['id'] ?? null,
            'nama_mk' => $matakuliah['nama_mk'] ?? null,
            'kode_mk' => $matakuliah['kode_mk'] ?? null,
            'sks' => $matakuliah['sks'] ?? null,
        ] : null;
    }
}
