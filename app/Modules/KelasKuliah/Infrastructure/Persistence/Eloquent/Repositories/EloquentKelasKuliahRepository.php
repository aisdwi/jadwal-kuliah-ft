<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahAcademicPeriodDefaults;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Support\Facades\DB;

class EloquentKelasKuliahRepository implements KelasKuliahRepository
{
    public function __construct(protected KelasKuliahModel $model) {}

    public function all(array $filters = []): PagedResult
    {
        $query = $this->baseListQuery();
        (new KelasKuliahListFilter())->apply($query, $filters);

        return (new KelasKuliahPagedResultFactory())->make($query, $filters['per_page'] ?? 'all');
    }

    private function baseListQuery()
    {
        return $this->model->newQuery()
            ->with(KelasKuliahEloquentRelations::WITHS)
            ->filterByJurusan('matakuliah')
            ->orderBy('id');
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()
            ->with(KelasKuliahEloquentRelations::WITHS)
            ->filterByJurusan('matakuliah')
            ->find($id);
    }

    public function findByKelas(int $kelasId): array
    {
        return $this->model->newQuery()
            ->with(KelasKuliahEloquentRelations::WITHS)
            ->filterByJurusan('matakuliah')
            ->where('kelas_id', $kelasId)
            ->get()
            ->all();
    }

    /**
     * @return list<KelasKuliahOffering>
     */
    public function findOfferingsByTeachingAssignment(int $kelasId, int $mataKuliahId, int $dosenId, ?int $ignoreId = null): array
    {
        $query = $this->model->newQuery()
            ->select(['id', 'kelas_id', 'matakuliah_id', 'dosen_id', 'jumlah_mahasiswa'])
            ->filterByJurusan('matakuliah')
            ->where('kelas_id', $kelasId)
            ->where('matakuliah_id', $mataKuliahId)
            ->where('dosen_id', $dosenId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query
            ->get()
            ->map(fn (KelasKuliahModel $model) => (new KelasKuliahOfferingMapper())->fromModel($model))
            ->values()
            ->all();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $kk = $this->model->newQuery()->create($this->modelPayload(KelasKuliahAcademicPeriodDefaults::fillData($data)));
            $this->syncTeachingTeam($kk, $data);

            return $kk->load(KelasKuliahEloquentRelations::WITHS);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $kk = $this->model->newQuery()->filterByJurusan('matakuliah')->find($id);
            if ($kk) {
                $kk->update($this->modelPayload(KelasKuliahAcademicPeriodDefaults::fillData($data)));
                $this->syncTeachingTeam($kk, $data);
                $kk->load(KelasKuliahEloquentRelations::WITHS);
            }

            return $kk;
        });
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->filterByJurusan('matakuliah')->whereKey($id)->delete();
    }

    private function modelPayload(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'dosen_id',
            'matakuliah_id',
            'kelas_id',
            'jumlah_mahasiswa',
            'semester_tipe',
        ]));
    }

    private function syncTeachingTeam(KelasKuliahModel $kelasKuliah, array $data): void
    {
        $team = $data['dosen_team'] ?? null;
        if (!is_array($team)) {
            return;
        }

        $syncPayload = [];
        foreach ($team as $entry) {
            if (!is_array($entry) || empty($entry['dosen_id'])) {
                continue;
            }

            $syncPayload[(int) $entry['dosen_id']] = [
                'preferred_slot_id' => empty($entry['preferred_slot_id']) ? null : (int) $entry['preferred_slot_id'],
                'is_external' => filter_var($entry['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if ($syncPayload !== []) {
            $kelasKuliah->dosens()->sync($syncPayload);
        }
    }

}
