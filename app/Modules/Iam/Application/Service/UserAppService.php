<?php

namespace App\Modules\Iam\Application\Service;

use App\Modules\Iam\Domain\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UserAppService
{
    public function __construct(protected UserRepository $repo) {}

    public function list(?string $search = null, int|string $perPage = 'all')
    {
        return $this->repo->all($search, $perPage);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id);
    }

    public function listRoles(): array
    {
        return $this->repo->findAllRoles();
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = Arr::only($data, [
            'nama_user',
            'email',
            'role_id',
            'jurusan_id',
            'program_studi_id',
            'dosen_id',
        ]);

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}
