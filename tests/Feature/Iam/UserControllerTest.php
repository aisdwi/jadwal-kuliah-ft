<?php

namespace Tests\Feature\Iam;

use App\Domain\Iam\Entity\Role;
use App\Domain\Iam\Entity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Tests: UserController
 *
 * Integration tests hitting actual HTTP endpoints.
 * Uses RefreshDatabase to isolate test data (in-memory SQLite).
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Legacy API v1/User domain test; current API surface is /api/v2 with module models.');
    }

    // ── INDEX ─────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_users(): void
    {
        // Seed test data
        $user1 = User::fromDb(
            1, 'User One', 'user1@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );
        $user2 = User::fromDb(
            2, 'User Two', 'user2@example.com', 'hash', 2, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        // Mock repository if needed or use actual database
        // For now, test structure shows expected behavior
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'nama_user', 'email', 'role_id',
                ]
            ],
        ]);
    }

    public function test_index_with_search_parameter(): void
    {
        $response = $this->getJson('/api/v1/users?search=john');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_index_with_pagination(): void
    {
        $response = $this->getJson('/api/v1/users?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'total',
            'current_page',
            'per_page',
            'last_page',
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────────────────

    public function test_store_creates_user_with_valid_data(): void
    {
        $data = [
            'nama_user' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'nama_user', 'email', 'role_id',
        ]);
    }

    public function test_store_requires_email(): void
    {
        $data = [
            'nama_user' => 'User',
            'password' => 'pass',
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_store_requires_unique_email(): void
    {
        // Pre-create a user
        User::fromDb(
            1, 'Existing', 'exist@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $data = [
            'nama_user' => 'Another',
            'email' => 'exist@example.com',
            'password' => 'pass123',
            'role_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_store_with_optional_fields(): void
    {
        $data = [
            'nama_user' => 'Full User',
            'email' => 'full@example.com',
            'password' => 'secure123',
            'role_id' => 2,
            'jurusan_id' => 5,
            'program_studi_id' => 10,
            'dosen_id' => 3,
        ];

        $response = $this->postJson('/api/v1/users', $data);

        $response->assertStatus(201);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────

    public function test_show_returns_user_by_id(): void
    {
        $user = User::fromDb(
            1, 'John', 'john@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $response = $this->getJson('/api/v1/users/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'nama_user', 'email',
        ]);
    }

    public function test_show_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/v1/users/999');

        $response->assertStatus(404);
    }

    // ── UPDATE ────────────────────────────────────────────────────────────

    public function test_update_modifies_user(): void
    {
        $user = User::fromDb(
            1, 'Old Name', 'old@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $data = [
            'nama_user' => 'New Name',
            'email' => 'new@example.com',
            'role_id' => 2,
        ];

        $response = $this->patchJson('/api/v1/users/1', $data);

        $response->assertStatus(200);
        $response->assertJsonPath('nama_user', 'New Name');
    }

    public function test_update_without_password_preserves_current(): void
    {
        $user = User::fromDb(
            1, 'User', 'user@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $data = [
            'nama_user' => 'Updated',
            'email' => 'updated@example.com',
        ];

        $response = $this->patchJson('/api/v1/users/1', $data);

        $response->assertStatus(200);
    }

    public function test_update_validates_email_uniqueness(): void
    {
        $user1 = User::fromDb(
            1, 'User 1', 'user1@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $user2 = User::fromDb(
            2, 'User 2', 'user2@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $data = [
            'nama_user' => 'Modified',
            'email' => 'user1@example.com',
        ];

        $response = $this->patchJson('/api/v1/users/2', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // ── DELETE ────────────────────────────────────────────────────────────

    public function test_destroy_deletes_user(): void
    {
        $user = User::fromDb(
            1, 'To Delete', 'delete@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $response = $this->deleteJson('/api/v1/users/1');

        $response->assertStatus(204);
    }

    // ── ROLES ─────────────────────────────────────────────────────────────

    public function test_roles_returns_list_of_roles(): void
    {
        // Assuming roles are seeded or exist in the database
        $response = $this->getJson('/api/v1/users/roles');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'role'],
            ]
        ]);
    }

    // ── RESPONSE FORMAT ───────────────────────────────────────────────────

    public function test_index_response_includes_nested_relations(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'nama_user', 'email', 'role_id',
                    'role', 'jurusan', 'program_studi', 'dosen',
                ]
            ],
        ]);
    }

    public function test_show_includes_related_role(): void
    {
        $user = User::fromDb(
            1, 'User', 'user@example.com', 'hash', 1, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
        );

        $response = $this->getJson('/api/v1/users/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'nama_user', 'email', 'role_id', 'role',
        ]);
    }
}
