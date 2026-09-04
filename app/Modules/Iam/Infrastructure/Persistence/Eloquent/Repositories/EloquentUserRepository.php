<?php

namespace App\Modules\Iam\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Iam\Domain\Repositories\UserRepository;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository implements UserRepository
{
    public function __construct(protected UserModel $model) {}

    public function all(?string $search = null, int|string $perPage = 'all')
    {
        $query = $this->model->newQuery()
            ->with(['role:id,role', 'jurusan:id,nama_jurusan', 'programStudi:id,jurusan_id,nama_prodi', 'programStudi.jurusan:id,nama_jurusan', 'dosen:id,nama_lengkap'])
            ->orderBy('nama_user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_user', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($perPage === 'all') {
            return $query->get();
        }

        return $query->paginate(max(1, (int) $perPage));
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()
            ->with(['role:id,role', 'jurusan:id,nama_jurusan', 'programStudi:id,jurusan_id,nama_prodi', 'programStudi.jurusan:id,nama_jurusan', 'dosen:id,nama_lengkap'])
            ->find($id);
    }

    public function findByLoginIdentifier(string $identifier)
    {
        $query = $this->model->newQuery()
            ->where('email', $identifier)
            ->orWhere('nama_user', $identifier);

        if (is_numeric($identifier)) {
            $query->orWhereHas('dosen', function ($subQuery) use ($identifier) {
                $subQuery->where('nip', $identifier);
            });
        }

        return $query->first();
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $user = $this->model->newQuery()->find($id);
        if ($user) {
            $user->update($data);
        }
        return $user;
    }

    public function delete(int $id)
    {
        return $this->model->newQuery()->whereKey($id)->delete();
    }

    public function findAllRoles(): array
    {
        return RoleModel::orderBy('id')->get()->map(fn ($r) => ['id' => $r->id, 'role' => RoleName::normalize($r->role)])->all();
    }
}
