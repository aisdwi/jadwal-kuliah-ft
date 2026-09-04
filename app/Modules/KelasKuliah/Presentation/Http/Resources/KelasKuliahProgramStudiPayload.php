<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahProgramStudiPayload
{
    public static function from(?object $programStudi): ?array
    {
        if (!$programStudi) {
            return null;
        }

        return [
            'id' => $programStudi->id,
            'nama_prodi' => $programStudi->nama_prodi,
            'jurusan' => $programStudi->jurusan ? [
                'id' => $programStudi->jurusan->id,
                'nama_jurusan' => $programStudi->jurusan->nama_jurusan,
            ] : null,
        ];
    }
}
