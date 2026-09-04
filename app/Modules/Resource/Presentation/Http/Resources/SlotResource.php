<?php

namespace App\Modules\Resource\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

class SlotResource
{
    public static function toArray($model): array
    {
        if (!$model) {
            return [];
        }

        return [
            'id' => $model->id,
            'hari_id' => $model->hari_id,
            'jurusan_ids' => $model->relationLoaded('jurusans')
                ? $model->jurusans->pluck('id')->values()->all()
                : [],
            'jurusans' => $model->relationLoaded('jurusans')
                ? $model->jurusans->map(fn ($jurusan) => [
                    'id' => $jurusan->id,
                    'nama_jurusan' => $jurusan->nama_jurusan,
                ])->values()->all()
                : [],
            'hari' => $model->hari ? [
                'id' => $model->hari->id,
                'nama_hari' => $model->hari->nama_hari,
            ] : null,
            'waktu_id' => $model->waktu_id,
            'waktu' => $model->waktu ? [
                'id' => $model->waktu->id,
                'pukul' => $model->waktu->pukul,
                'sks' => $model->waktu->sks,
                'jam_index' => $model->waktu->jam_index,
            ] : null,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }

    public static function pagedToArray($pagedResult): array
    {
        return PagedResponseFormatter::formatNested($pagedResult, [self::class, 'toArray']);
    }
}
