<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\Schema;

final class KelasKuliahListFilter
{
    private KelasKuliahSearchFilter $searchFilter;

    public function __construct(?KelasKuliahSearchFilter $searchFilter = null)
    {
        $this->searchFilter = $searchFilter ?? new KelasKuliahSearchFilter();
    }

    public function apply($query, array $filters): void
    {
        $this->applyRelationFilter($query, $filters, 'program_studi_id', 'kelas');
        $this->applyRelationFilter($query, $filters, 'jurusan_id', 'matakuliah');
        $this->applyRelationFilter($query, $filters, 'semester', 'kelas');
        $this->applySemesterTipeFilter($query, $filters);
        $this->applyDirectFilter($query, $filters, 'dosen_id');
        $this->applyScheduledFilter($query, $filters);
        $this->searchFilter->apply($query, $filters['search'] ?? null);
    }

    private function applyRelationFilter($query, array $filters, string $field, string $relation): void
    {
        if (!empty($filters[$field])) {
            $query->whereHas($relation, fn ($q) => $q->where($field, $filters[$field]));
        }
    }

    private function applyDirectFilter($query, array $filters, string $field): void
    {
        if (!empty($filters[$field])) {
            $query->where($field, $filters[$field]);
        }
    }

    private function applySemesterTipeFilter($query, array $filters): void
    {
        $tipe = strtolower(trim((string) ($filters['semester_tipe'] ?? '')));

        if ($tipe === 'ganjil') {
            if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
                $query->where('semester_tipe', 'ganjil');
                return;
            }
            $query->whereHas('matakuliah', fn ($q) => $q->whereRaw('semester % 2 != 0'));
        } elseif ($tipe === 'genap') {
            if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
                $query->where('semester_tipe', 'genap');
                return;
            }
            $query->whereHas('matakuliah', fn ($q) => $q->whereRaw('semester % 2 = 0'));
        }
    }

    private function applyScheduledFilter($query, array $filters): void
    {
        $isScheduled = $filters['is_scheduled'] ?? null;

        if ($isScheduled === true) {
            $query->whereHas('jadwals');
        } elseif ($isScheduled === false) {
            $query->whereDoesntHave('jadwals');
        }
    }

}
