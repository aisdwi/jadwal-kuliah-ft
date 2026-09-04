<?php

namespace App\Modules\Iam\Domain\Repositories;

interface UserRepository
{
    public function all();

    public function findById(int $id);

    public function findByLoginIdentifier(string $identifier);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function findAllRoles(): array;
}
