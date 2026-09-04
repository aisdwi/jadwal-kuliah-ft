<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class JurusanReferencePayload
{
    public static function from(mixed $jurusan): array
    {
        return [
            'id' => is_array($jurusan) ? $jurusan['id'] : $jurusan->id,
            'nama_jurusan' => is_array($jurusan) ? $jurusan['nama_jurusan'] : $jurusan->nama_jurusan,
        ];
    }
}
