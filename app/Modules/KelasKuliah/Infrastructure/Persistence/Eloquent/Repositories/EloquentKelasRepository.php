<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasModel;
use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentKelasRepository implements KelasRepository
{
    public function __construct(protected KelasModel $model) {}

    public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): PagedResult
    {
        $query = $this->model->newQuery()
            ->with(['programStudi:id,nama_prodi', 'jurusan:id,nama_jurusan'])
            ->filterByJurusan()
            ->orderBy('program_studi_id')
            ->orderBy('semester')
            ->orderBy('nama_kelas');

        if ($search) {
            $query->where('nama_kelas', 'like', "%{$search}%");
        }
        if ($programStudiId !== null) {
            $query->where('program_studi_id', $programStudiId);
        }
        if ($semester !== null) {
            $query->where('semester', $semester);
        }

        if ($perPage === 'all') {
            $items = $query->get();
            return new PagedResult($items->all());
        }

        $paged = $query->paginate(max(1, (int) $perPage));
        return new PagedResult(
            items: $paged->items(),
            total: $paged->total(),
            currentPage: $paged->currentPage(),
            perPage: $paged->perPage(),
            lastPage: $paged->lastPage(),
        );
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()
            ->with(['programStudi:id,nama_prodi', 'jurusan:id,nama_jurusan'])
            ->filterByJurusan()
            ->find($id);
    }

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array
    {
        $query = $this->model->newQuery()
            ->filterByJurusan()
            ->where('program_studi_id', $programStudiId);
        if ($semester !== null) {
            $query->where('semester', $semester);
        }
        return $query->get()->all();
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $k = $this->model->newQuery()->filterByJurusan()->find($id);
        if ($k) {
            $k->update($data);
        }
        return $k;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'kelas_id')) {
                DB::table('kelas_kuliah')->where('kelas_id', $id)->delete();
            }

            $this->model->newQuery()->filterByJurusan()->whereKey($id)->delete();
        });
    }
}
