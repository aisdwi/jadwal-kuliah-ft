<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Resource\Domain\Repositories\RuanganRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentRuanganRepository implements RuanganRepository
{
    public function __construct(protected RuanganModel $model) {}

    private function scopedQuery(): Builder
    {
        $query = $this->model->newQuery()->with([
            'gedung',
            'jurusans:id,nama_jurusan',
        ]);
        $user = auth()->user();
        $roleName = $user?->role?->role ?? null;
        $jurusanId = $user?->jurusan_id;

        if ($jurusanId && RoleName::defaultsToOwnJurusan($roleName)) {
            $query->whereHas('jurusans', function (Builder $subQuery) use ($jurusanId) {
                $subQuery->where('jurusan.id', $jurusanId);
            });
        }

        return $query;
    }

    public function all(?string $search = null, ?int $jurusanId = null): Collection
    {
        $q = $this->scopedQuery();
        if ($jurusanId !== null) {
            $q->whereHas('jurusans', function (Builder $subQuery) use ($jurusanId) {
                $subQuery->where('jurusan.id', $jurusanId);
            });
        }
        if ($search) {
            $q->where('ruangan', 'like', "%{$search}%");
        }
        return $q->get();
    }

    public function allForAllocation(): Collection
    {
        return $this->model->newQuery()
            ->with('gedung:id,nama_gedung')
            ->get();
    }

    public function findById(int $id)
    {
        return $this->scopedQuery()->find($id);
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $r = $this->scopedQuery()->find($id);
        if ($r) {
            $r->update($data);
        }
        return $r;
    }

    public function delete(int $id): void
    {
        $this->scopedQuery()->whereKey($id)->delete();
    }
}
