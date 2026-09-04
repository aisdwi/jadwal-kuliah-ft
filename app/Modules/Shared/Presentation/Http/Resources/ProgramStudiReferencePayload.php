<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class ProgramStudiReferencePayload
{
    public static function from(mixed $programStudi): array
    {
        return [
            'id' => is_array($programStudi) ? $programStudi['id'] : $programStudi->id,
            'nama_prodi' => is_array($programStudi) ? ($programStudi['nama_prodi'] ?? null) : $programStudi->nama_prodi,
            'jurusan_id' => is_array($programStudi) ? $programStudi['jurusan_id'] : $programStudi->jurusan_id,
        ];
    }
}
