<?php

namespace Tests\Feature\KelasKuliah;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Tests: KelasKuliah Controller
 *
 * Integration tests for Course Offering endpoints.
 */
class KelasKuliahControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Legacy API v1 test; current kelas kuliah API lives under /api/v2 with auth and prodi context.');
    }

    // ── INDEX ─────────────────────────────────────────────────────────────

    public function test_index_returns_kelas_kuliah_list(): void
    {
        $response = $this->getJson('/api/v1/kelas-kuliah');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_index_with_filters(): void
    {
        $response = $this->getJson('/api/v1/kelas-kuliah?program_studi_id=5&semester=3');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_index_with_pagination(): void
    {
        $response = $this->getJson('/api/v1/kelas-kuliah?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'total',
            'current_page',
            'per_page',
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────────────────

    public function test_store_creates_kelas_kuliah(): void
    {
        $data = [
            'dosen_id' => 1,
            'matakuliah_id' => 10,
            'kelas_id' => 1,
            'jumlah_mahasiswa' => 40,
        ];

        $response = $this->postJson('/api/v1/kelas-kuliah', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'dosen_id', 'matakuliah_id',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $data = [
            'dosen_id' => 1,
        ];

        $response = $this->postJson('/api/v1/kelas-kuliah', $data);

        $response->assertStatus(422);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────

    public function test_show_returns_kelas_kuliah(): void
    {
        $response = $this->getJson('/api/v1/kelas-kuliah/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'dosen_id', 'matakuliah_id',
        ]);
    }

    public function test_show_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/v1/kelas-kuliah/999');

        $response->assertStatus(404);
    }

    // ── UPDATE ────────────────────────────────────────────────────────────

    public function test_update_modifies_kelas_kuliah(): void
    {
        $data = [
            'dosen_id' => 2,
            'matakuliah_id' => 11,
            'kelas_id' => 1,
            'jumlah_mahasiswa' => 42,
        ];

        $response = $this->patchJson('/api/v1/kelas-kuliah/1', $data);

        $response->assertStatus(200);
    }

    // ── DELETE ────────────────────────────────────────────────────────────

    public function test_destroy_deletes_kelas_kuliah(): void
    {
        $response = $this->deleteJson('/api/v1/kelas-kuliah/1');

        $response->assertStatus(204);
    }
}
