<?php

namespace Tests\Unit\Application\Iam;

use App\Modules\Iam\Application\Service\UserAppService;
use App\Modules\Iam\Domain\Repositories\UserRepository;
use Tests\TestCase;

class UserAppServiceTest extends TestCase
{
    private FakeUserRepository $repo;
    private UserAppService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new FakeUserRepository();
        $this->service = new UserAppService($this->repo);
    }

    public function test_list_delegates_search_and_pagination_to_repository(): void
    {
        $this->repo->allResult = collect([(object) ['id' => 1, 'nama_user' => 'John Doe']]);

        $result = $this->service->list('John', 10);

        $this->assertCount(1, $result);
        $this->assertSame(['John', 10], $this->repo->allCalls[0]);
    }

    public function test_find_by_id_returns_repository_result(): void
    {
        $this->repo->users[7] = (object) ['id' => 7, 'nama_user' => 'Test User'];

        $result = $this->service->findById(7);

        $this->assertSame(7, $result->id);
    }

    public function test_list_roles_returns_repository_roles(): void
    {
        $this->repo->roles = [['id' => 1, 'role' => 'Super Admin']];

        $this->assertSame($this->repo->roles, $this->service->listRoles());
    }

    public function test_persist_creates_user_with_hashed_password(): void
    {
        $created = $this->service->persist([
            'nama_user' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret',
            'role_id' => 1,
            'ignored' => 'value',
        ]);

        $this->assertSame(1, $created->id);
        $this->assertSame('New User', $this->repo->createdPayload['nama_user']);
        $this->assertArrayNotHasKey('ignored', $this->repo->createdPayload);
        $this->assertNotSame('secret', $this->repo->createdPayload['password']);
    }

    public function test_persist_updates_without_overwriting_password_when_empty(): void
    {
        $updated = $this->service->persist([
            'nama_user' => 'Updated User',
            'email' => 'updated@example.com',
            'password' => '',
            'role_id' => 2,
        ], 5);

        $this->assertSame(5, $updated->id);
        $this->assertArrayNotHasKey('password', $this->repo->updatedPayload);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $this->service->delete(5);

        $this->assertSame(5, $this->repo->deletedId);
    }
}

class FakeUserRepository implements UserRepository
{
    public mixed $allResult;
    public array $allCalls = [];
    public array $users = [];
    public array $roles = [];
    public array $createdPayload = [];
    public array $updatedPayload = [];
    public ?int $deletedId = null;

    public function __construct()
    {
        $this->allResult = collect();
    }

    public function all(?string $search = null, int|string $perPage = 'all')
    {
        $this->allCalls[] = [$search, $perPage];

        return $this->allResult;
    }

    public function findById(int $id)
    {
        return $this->users[$id] ?? null;
    }

    public function findByLoginIdentifier(string $identifier)
    {
        return null;
    }

    public function create(array $data)
    {
        $this->createdPayload = $data;

        return (object) array_merge(['id' => 1], $data);
    }

    public function update(int $id, array $data)
    {
        $this->updatedPayload = $data;

        return (object) array_merge(['id' => $id], $data);
    }

    public function delete(int $id)
    {
        $this->deletedId = $id;
    }

    public function findAllRoles(): array
    {
        return $this->roles;
    }
}
