<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class HariReferencePayload
{
    public static function from(mixed $hari): array
    {
        return [
            'id' => is_array($hari) ? $hari['id'] : $hari->id,
            'nama_hari' => is_array($hari) ? $hari['nama_hari'] : $hari->nama_hari,
        ];
    }
}
