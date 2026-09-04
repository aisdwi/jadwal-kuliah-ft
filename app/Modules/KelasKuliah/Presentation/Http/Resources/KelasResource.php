<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

class KelasResource
{
    public static function toArray($model): array
    {
        return [
            'id' => $model->id,
            'nama_kelas' => $model->nama_kelas,
            'semester' => $model->semester,
            'program_studi_id' => $model->program_studi_id,
            'program_studi' => $model->programStudi ? [
                'id' => $model->programStudi->id,
                'nama_prodi' => $model->programStudi->nama_prodi,
            ] : null,
            'jurusan_id' => $model->jurusan_id,
            'jurusan' => $model->jurusan ? [
                'id' => $model->jurusan->id,
                'nama_jurusan' => $model->jurusan->nama_jurusan,
            ] : null,
        ];
    }

    public static function pagedToArray($pagedResult): array
    {
        return PagedResponseFormatter::format($pagedResult, [self::class, 'toArray']);
    }
}
