<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\MataKuliahModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentMataKuliahRepository implements MataKuliahRepository
{
    private const PROGRAM_STUDI_RELATION = 'programStudi:id,nama_prodi';
    private const JURUSAN_RELATION = 'jurusan:id,nama_jurusan';

    public function __construct(protected MataKuliahModel $model) {}

    public function all(
        ?string    $search          = null,
        ?int       $programStudiId  = null,
        ?int       $semester        = null,
        ?string    $semesterTipe    = null,
        bool       $filterByJurusan = true,
        int|string $perPage         = 'all'
    ) {
        $query = $this->model->newQuery()->with([self::PROGRAM_STUDI_RELATION, self::JURUSAN_RELATION]);

        if ($filterByJurusan) {
            $query->filterByJurusan();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mk', 'like', "%{$search}%")
                  ->orWhere('kode_mk', 'like', "%{$search}%");
            });
        }

        if ($programStudiId) {
            $query->where('program_studi_id', $programStudiId);
        }

        if ($semester) {
            $query->where('semester', $semester);
        }

        if ($semesterTipe === 'ganjil') {
            $query->whereRaw('semester % 2 = 1');
        } elseif ($semesterTipe === 'genap') {
            $query->whereRaw('semester % 2 = 0');
        }

        $query->orderBy('nama_mk');

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate(max(1, (int)$perPage));
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->with([self::PROGRAM_STUDI_RELATION, self::JURUSAN_RELATION])->find($id);
    }

    public function findAccessibleById(int $id)
    {
        return $this->model->newQuery()
            ->with([self::PROGRAM_STUDI_RELATION, self::JURUSAN_RELATION])
            ->filterByJurusan()
            ->find($id);
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
            if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'matakuliah_id')) {
                DB::table('kelas_kuliah')->where('matakuliah_id', $id)->delete();
            }

            $this->model->newQuery()->whereKey($id)->delete();
        });
    }
}
