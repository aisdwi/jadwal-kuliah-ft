<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

final class KelasKuliahSearchFilter
{
    private const SEARCH_RELATIONS = [
        'matakuliah' => ['nama_mk', 'kode_mk'],
        'matakuliah.programStudi' => ['nama_prodi'],
        'matakuliah.programStudi.jurusan' => ['nama_jurusan'],
        'dosen' => ['nama_lengkap'],
        'dosens' => ['nama_lengkap'],
        'kelas' => ['nama_kelas'],
        'jadwals.slot.hari' => ['nama_hari'],
        'jadwals.slot.waktu' => ['pukul'],
        'jadwals.ruangan' => ['ruangan'],
    ];

    public function apply($query, mixed $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($scopedQuery) use ($search) {
            foreach (self::SEARCH_RELATIONS as $relation => $columns) {
                $this->addRelationSearch($scopedQuery, $relation, $columns, (string) $search);
            }

            $scopedQuery->orWhere('jumlah_mahasiswa', 'like', "%{$search}%");
        });
    }

    private function addRelationSearch($query, string $relation, array $columns, string $search): void
    {
        $query->orWhereHas($relation, function ($relationQuery) use ($columns, $search) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $relationQuery->{$method}($column, 'like', "%{$search}%");
            }
        });
    }
}
