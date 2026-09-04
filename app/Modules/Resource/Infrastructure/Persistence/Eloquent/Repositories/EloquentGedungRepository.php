<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Resource\Domain\Repositories\GedungRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\GedungModel;
use Illuminate\Support\Collection;

class EloquentGedungRepository implements GedungRepository
{
    public function __construct(
        private readonly GedungModel $model,
    ) {}

    public function all(): Collection
    {
        return $this->model->newQuery()
            ->select(['id', 'nama_gedung'])
            ->orderBy('id')
            ->get();
    }
}
