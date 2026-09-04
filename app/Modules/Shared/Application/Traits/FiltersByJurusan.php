<?php

namespace App\Modules\Shared\Application\Traits;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait FiltersByJurusan
{
    public function scopeFilterByJurusan(Builder $query, $relation = null)
    {
        $programStudiId = $this->scopedProgramStudiId();
        if ($programStudiId !== null) {
            return $this->applyProgramStudiFilter($query, $programStudiId, $relation);
        }

        $jurusanId = $this->scopedJurusanId();
        if ($jurusanId === null) {
            return $query;
        }

        return $this->applyJurusanFilter($query, $jurusanId, $relation);
    }

    private function scopedJurusanId(): ?int
    {
        $user = auth()->user();
        if (! $user || ! $user->role || ! RoleName::isJurusanScoped($user->role->role)) {
            return null;
        }

        $jurusanId = $user->jurusan_id ?: $user->programStudi?->jurusan_id;
        if (! $jurusanId && $user->program_studi_id) {
            $jurusanId = ProgramStudiModel::whereKey($user->program_studi_id)->value('jurusan_id');
        }

        return $jurusanId ? (int) $jurusanId : null;
    }

    private function scopedProgramStudiId(): ?int
    {
        $user = auth()->user();
        if (! $user || ! $user->role || RoleName::normalize($user->role->role) !== 'Koordinator Program Studi') {
            return null;
        }

        return $user->program_studi_id ? (int) $user->program_studi_id : null;
    }

    private function applyJurusanFilter(Builder $query, int $jurusanId, $relation): Builder
    {
        if ($relation) {
            $query->whereHas($relation, function ($q) use ($jurusanId) {
                $q->where('jurusan_id', $jurusanId);
            });

            return $query;
        }

        $query->where($this->getTable() . '.jurusan_id', $jurusanId);

        return $query;
    }

    private function applyProgramStudiFilter(Builder $query, int $programStudiId, $relation): Builder
    {
        if ($relation) {
            $query->whereHas($relation, function ($q) use ($programStudiId) {
                $q->where('program_studi_id', $programStudiId);
            });

            return $query;
        }

        if (Schema::hasColumn($this->getTable(), 'program_studi_id')) {
            $query->where($this->getTable() . '.program_studi_id', $programStudiId);
        }

        return $query;
    }
}
