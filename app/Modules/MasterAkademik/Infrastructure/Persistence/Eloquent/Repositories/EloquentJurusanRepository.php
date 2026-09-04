<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\MasterAkademik\Domain\Repositories\JurusanRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;

class EloquentJurusanRepository implements JurusanRepository
{
    public function __construct(protected JurusanModel $model) {}

    public function all(?string $search = null, int|string $perPage = 'all')
    {
        $query = $this->model->newQuery()->orderBy('nama_jurusan');

        if ($search) {
            $query->where('nama_jurusan', 'like', "%{$search}%");
        }

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate(max(1, (int)$perPage));
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->find($id);
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
