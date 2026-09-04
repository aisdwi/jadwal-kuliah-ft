<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Resource\Domain\Repositories\HariRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\HariModel;
use Illuminate\Support\Collection;

class EloquentHariRepository implements HariRepository
{
    public function __construct(protected HariModel $model) {}

    public function all(?string $search = null): Collection
    {
        $q = $this->model->newQuery();
        if ($search) {
            $q->where('nama_hari', 'like', "%{$search}%");
        }
        return $q->get();
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
        $h = $this->model->newQuery()->find($id);
        if ($h) {
            $h->update($data);
        }
        return $h;
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->whereKey($id)->delete();
    }
}
