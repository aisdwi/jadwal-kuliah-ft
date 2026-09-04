<?php

namespace Tests\Feature\Laporan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Tests: Export Jadwal Controller
 *
 * Integration tests for Excel export endpoints.
 * Tests end-to-end flow: Controller → Handler → ExcelExporter Port → Infrastructure.
 */
class ExportJadwalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Legacy API v1 export test; current export route is /api/v2/prodi/{programStudiId}/jadwal/export.');
    }

    // ── EXPORT JADWAL PRODI ───────────────────────────────────────────────

    public function test_export_jadwal_returns_excel_file(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 1,
            'semester' => 3,
        ]);

        // Should return 200 with Excel file content
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_jadwal_without_semester(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 1,
        ]);

        $response->assertStatus(200);
    }

    public function test_export_jadwal_validates_prodi_id(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'semester' => 3,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['program_studi_id']);
    }

    // ── FILE NAME ─────────────────────────────────────────────────────────

    public function test_export_file_has_descriptive_name(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 1,
            'semester' => 3,
        ]);

        // Check if Content-Disposition header has filename
        $this->assertTrue(
            str_contains(
                (string) $response->headers->get('Content-Disposition'),
                'Jadwal_'
            )
        );
    }

    // ── ERROR HANDLING ────────────────────────────────────────────────────

    public function test_export_handles_missing_prodi_gracefully(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 999,
            'semester' => 1,
        ]);

        // Should still export even if prodi doesn't exist (with fallback name)
        $response->assertStatus(200);
    }

    public function test_export_handles_no_jadwal_data(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 1,
            'semester' => 99,
        ]);

        // Should still return Excel file even if no data
        $response->assertStatus(200);
    }

    // ── RESOURCE EFFICIENCY ───────────────────────────────────────────────

    public function test_export_response_has_reasonable_size(): void
    {
        $response = $this->postJson('/api/v1/laporan/export-jadwal', [
            'program_studi_id' => 1,
        ]);

        $response->assertStatus(200);
        $contentLength = strlen($response->getContent());

        // Excel files should be at least 1KB
        $this->assertGreaterThan(1024, $contentLength);
    }
}
