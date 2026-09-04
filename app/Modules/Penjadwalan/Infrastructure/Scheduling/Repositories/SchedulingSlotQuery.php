<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel;
use Illuminate\Database\Eloquent\Builder;

final class SchedulingSlotQuery
{
    public static function forJurusan(?int $jurusanId): Builder
    {
        return SlotModel::query()
            ->when($jurusanId !== null, function (Builder $query) use ($jurusanId) {
                $query->whereHas('jurusans', fn (Builder $jurusanQuery) => $jurusanQuery->where('jurusan.id', $jurusanId));
            });
    }
}
