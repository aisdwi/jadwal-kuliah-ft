<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\MasterAkademik\Domain\Repositories\DosenRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentDosenRepository implements DosenRepository
{
    public function __construct(protected DosenModel $model) {}

    public function all(?string $search = null, ?int $jurusanId = null, bool $filterByJurusan = true, int|string $perPage = 'all', ?int $excludeJurusanId = null)
    {
        $query = $this->model->newQuery()->with('jurusan:id,nama_jurusan');

        if ($filterByJurusan) {
            $query->filterByJurusan();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('inisial', 'like', "%{$search}%");
            });
        }

        if ($jurusanId) {
            $query->where('jurusan_id', $jurusanId);
        }

        if ($excludeJurusanId) {
            $query->where('jurusan_id', '!=', $excludeJurusanId);
        }

        $query->orderBy('nama_lengkap');

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate(max(1, (int)$perPage));
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->with('jurusan:id,nama_jurusan')->find($id);
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $model = $this->model->newQuery()->find($id);
        if ($model) {
            $model->update($data);
        }
        return $model;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            // kelas_kuliah.dosen_id is RESTRICT; pivot kelas_kuliah_dosen.dosen_id is CASCADE.
            if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'dosen_id')) {
                DB::table('kelas_kuliah')->where('dosen_id', $id)->delete();
            }

            $this->model->newQuery()->whereKey($id)->delete();
        });
    }
}
