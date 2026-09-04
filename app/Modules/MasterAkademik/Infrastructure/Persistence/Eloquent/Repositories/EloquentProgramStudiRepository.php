<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;

class EloquentProgramStudiRepository implements ProgramStudiRepository
{
    private const JURUSAN_RELATION = 'jurusan:id,nama_jurusan';

    public function __construct(protected ProgramStudiModel $model) {}

    public function all(?string $search = null, ?int $jurusanId = null, int|string $perPage = 'all')
    {
        $query = $this->model->newQuery()->with(self::JURUSAN_RELATION)->orderBy('nama_prodi');

        if ($search) {
            $query->where('nama_prodi', 'like', "%{$search}%");
        }

        if ($jurusanId) {
            $query->where('jurusan_id', $jurusanId);
        }

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate(max(1, (int)$perPage));
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->with(self::JURUSAN_RELATION)->find($id);
    }

    public function findAccessibleById(int $id)
    {
        return $this->model->newQuery()
            ->with(self::JURUSAN_RELATION)
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
        $this->model->newQuery()->whereKey($id)->delete();
    }
}
