<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use Illuminate\Database\Eloquent\Builder;

final class SchedulingKelasKuliahSearchFilter
{
    public function apply(Builder $query, mixed $search): void
    {
        if (empty($search)) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $this->addRelations($q, (string) $search);
            $q->orWhereHas('dosens', fn ($subQ) => $subQ->where('nama_lengkap', 'like', "%{$search}%"));
        });
    }

    private function addRelations(Builder $query, string $search): void
    {
        foreach ($this->relations() as $index => $searchRelation) {
            $method = $index === 0 ? 'whereHas' : 'orWhereHas';
            $query->{$method}($searchRelation['relation'], fn ($subQ) => $subQ->where($searchRelation['column'], 'like', "%{$search}%"));
        }
    }

    private function relations(): array
    {
        return [
            ['relation' => 'matakuliah', 'column' => 'nama_mk'],
            ['relation' => 'kelas', 'column' => 'nama_kelas'],
            ['relation' => 'dosen', 'column' => 'nama_lengkap'],
        ];
    }
}
