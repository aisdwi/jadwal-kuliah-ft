<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahDosenPayload
{
    public static function from(?object $dosen): ?array
    {
        if (!$dosen) {
            return null;
        }

        return [
            'id' => $dosen->id,
            'nama_lengkap' => $dosen->nama_lengkap,
            'inisial' => $dosen->inisial,
            'jurusan' => self::jurusan($dosen),
        ];
    }

    private static function jurusan(object $dosen): ?array
    {
        if (!$dosen->jurusan) {
            return null;
        }

        return [
            'id' => $dosen->jurusan->id,
            'nama_jurusan' => $dosen->jurusan->nama_jurusan,
        ];
    }
}
