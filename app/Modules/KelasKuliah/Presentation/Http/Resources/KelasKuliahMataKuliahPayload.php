<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahMataKuliahPayload
{
    public static function from(?object $mataKuliah): ?array
    {
        if (!$mataKuliah) {
            return null;
        }

        return [
            'id' => $mataKuliah->id,
            'nama_mk' => $mataKuliah->nama_mk,
            'kode_mk' => $mataKuliah->kode_mk,
            'sks' => $mataKuliah->sks,
            'semester' => $mataKuliah->semester,
            'program_studi' => KelasKuliahProgramStudiPayload::from($mataKuliah->programStudi),
        ];
    }
}
