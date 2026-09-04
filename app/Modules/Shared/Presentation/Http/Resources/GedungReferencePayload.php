<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class GedungReferencePayload
{
    public static function from(mixed $gedung): array
    {
        return [
            'id' => $gedung->id,
            'nama_gedung' => $gedung->nama_gedung,
        ];
    }
}
