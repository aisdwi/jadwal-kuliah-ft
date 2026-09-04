<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\Shared\Application\Service\AcademicScopeQuery;
use Illuminate\Pagination\LengthAwarePaginator;

final class SchedulingKelasKuliahListQuery
{
    public function __construct(
        private readonly SchedulingKelasKuliahFilter $filter = new SchedulingKelasKuliahFilter(),
        private readonly SchedulingKelasKuliahHydrator $hydrator = new SchedulingKelasKuliahHydrator(),
    ) {}

    public function getList(array $filters, mixed $perPage): mixed
    {
        $query = $this->filter->apply($this->baseListQuery(), $filters);

        if ($perPage === 'all') {
            return $query->get()->map(fn (KelasKuliahModel $model) => $this->hydrator->hydrate($model));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate((int) ($perPage ?: 10));
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (KelasKuliahModel $model) => $this->hydrator->hydrate($model))
        );

        return $paginator;
    }

    public function findDetail(int $id, array $scope): ?object
    {
        $query = AcademicScopeQuery::applyToKelasKuliahQuery($this->baseListQuery(), $scope);
        $model = $query->find($id);

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    private function baseListQuery()
    {
        return KelasKuliahModel::query()->with([
            'dosen',
            'dosens.jurusan',
            'matakuliah.programStudi.jurusan',
            'kelas',
            'kelas.programStudi',
            'ruangan',
            'slot.hari',
            'slot.waktu',
            'jadwals' => fn ($query) => $query->orderByDesc('id'),
        ]);
    }
}
