<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;
use Illuminate\Database\Eloquent\Builder;

final class JadwalScopedQuery
{
    public function __construct(private readonly JadwalModel $model) {}

    public function make(): Builder
    {
        $query = $this->model->newQuery();
        $user = auth()->user();
        $roleName = $user?->role?->role ?? null;
        $jurusanId = $user?->jurusan_id;

        if ($jurusanId && RoleName::isJurusanScoped($roleName)) {
            $query->whereHas('kelasKuliah.matakuliah', fn ($q) => $q->where('jurusan_id', $jurusanId));
        }

        return $query;
    }
}
