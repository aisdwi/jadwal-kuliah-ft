<?php

namespace App\Modules\Resource\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

class WaktuResource
{
    public static function toArray($model): array
    {
        if (!$model) {
            return [];
        }

        return [
            'id' => $model->id,
            'pukul' => $model->pukul,
            'sks' => $model->sks,
            'jam_index' => $model->jam_index,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }

    public static function pagedToArray($pagedResult): array
    {
        return PagedResponseFormatter::formatNested($pagedResult, [self::class, 'toArray']);
    }
}
