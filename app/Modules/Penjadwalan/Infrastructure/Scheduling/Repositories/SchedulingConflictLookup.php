<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\Shared\Application\Service\AcademicScopeQuery;

final class SchedulingConflictLookup
{
    public function __construct(private readonly SchedulingKelasKuliahHydrator $hydrator = new SchedulingKelasKuliahHydrator()) {}

    public function findKelasKuliahById(int $id, array $scope): ?object
    {
        $model = AcademicScopeQuery::applyToKelasKuliahQuery(KelasKuliahModel::query(), $scope)
            ->with($this->detailRelations())
            ->find($id);

        return $this->hydrateNullable($model);
    }

    public function findByRuangan(int $slotId, int $ruanganId, int $ignoreKelasKuliahId): ?object
    {
        $model = KelasKuliahModel::query()
            ->where('id', '!=', $ignoreKelasKuliahId)
            ->whereHas('jadwals', fn ($query) => $query->where('slot_id', $slotId)->where('ruangan_id', $ruanganId))
            ->with(['matakuliah', 'kelas', 'ruangan', 'slot.hari', 'slot.waktu', 'jadwals' => fn ($query) => $query->orderByDesc('id')])
            ->first();

        return $this->hydrateNullable($model);
    }

    public function findByKelas(int $kelasId, int $slotId, int $ignoreKelasKuliahId): ?object
    {
        $model = KelasKuliahModel::query()
            ->where('id', '!=', $ignoreKelasKuliahId)
            ->where('kelas_id', $kelasId)
            ->whereHas('jadwals', fn ($query) => $query->where('slot_id', $slotId))
            ->with(['matakuliah', 'kelas', 'slot.hari', 'slot.waktu', 'jadwals' => fn ($query) => $query->orderByDesc('id')])
            ->first();

        return $this->hydrateNullable($model);
    }

    public function findByDosen(array $dosenIds, int $slotId, int $ignoreKelasKuliahId): ?object
    {
        if (empty($dosenIds)) {
            return null;
        }

        $model = KelasKuliahModel::query()
            ->where('id', '!=', $ignoreKelasKuliahId)
            ->whereHas('jadwals', fn ($query) => $query->where('slot_id', $slotId))
            ->where(fn ($query) => $this->applyDosenFilter($query, $dosenIds))
            ->with(['matakuliah', 'kelas', 'dosen', 'dosens', 'slot.hari', 'slot.waktu', 'jadwals' => fn ($query) => $query->orderByDesc('id')])
            ->first();

        return $this->hydrateNullable($model);
    }

    private function applyDosenFilter($query, array $dosenIds): void
    {
        $query->whereIn('dosen_id', $dosenIds)
            ->orWhereHas('dosens', fn ($subQuery) => $subQuery->whereIn('dosen.id', $dosenIds));
    }

    private function detailRelations(): array
    {
        return [
            'dosen',
            'dosens.jurusan',
            'matakuliah.programStudi.jurusan',
            'kelas',
            'ruangan',
            'slot.hari',
            'slot.waktu',
            'jadwals' => fn ($query) => $query->orderByDesc('id'),
        ];
    }

    private function hydrateNullable(?KelasKuliahModel $model): ?KelasKuliahModel
    {
        return $model ? $this->hydrator->hydrate($model) : null;
    }
}
