<?php

namespace App\Modules\Resource\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

class RuanganResource
{
    public static function toArray($model): array
    {
        if (!$model) {
            return [];
        }

        return [
            'id' => $model->id,
            'gedung_id' => $model->gedung_id,
            'gedung' => $model->gedung ? [
                'id' => $model->gedung->id,
                'nama_gedung' => $model->gedung->nama_gedung,
            ] : null,
            'ruangan' => $model->ruangan,
            'kapasitas' => $model->kapasitas,
            'jurusan_ids' => $model->jurusans?->pluck('id')->values()->all() ?? [],
            'jurusans' => $model->jurusans?->map(fn ($jurusan) => [
                'id' => $jurusan->id,
                'nama_jurusan' => $jurusan->nama_jurusan,
            ])->values()->all() ?? [],
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }

    public static function pagedToArray($pagedResult): array
    {
        return PagedResponseFormatter::formatNested($pagedResult, [self::class, 'toArray']);
    }
}
