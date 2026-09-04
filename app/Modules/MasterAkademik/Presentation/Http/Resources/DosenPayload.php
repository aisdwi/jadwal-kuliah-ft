<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Resources;

final class DosenPayload
{
    public static function from(object $dosen): array
    {
        return [
            'id' => $dosen->id,
            'nip' => $dosen->nip,
            'nama_lengkap' => $dosen->nama_lengkap,
            'inisial' => $dosen->inisial,
            'jurusan_id' => $dosen->jurusan_id,
            'jurusan' => self::jurusan($dosen),
        ];
    }

    private static function jurusan(object $dosen): ?array
    {
        if (!$dosen->jurusan) {
            return null;
        }

        return [
            'id' => $dosen->jurusan_id,
            'nama_jurusan' => $dosen->jurusan->nama_jurusan,
        ];
    }
}
