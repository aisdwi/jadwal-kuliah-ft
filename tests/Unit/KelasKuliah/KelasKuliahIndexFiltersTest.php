<?php

namespace Tests\Unit\KelasKuliah;

use App\Modules\KelasKuliah\Presentation\Http\Support\KelasKuliahIndexFilters;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class KelasKuliahIndexFiltersTest extends TestCase
{
    public function test_builds_filters_from_request_inputs(): void
    {
        $class = KelasKuliahIndexFilters::class;
        $this->assertTrue(class_exists($class));

        $filters = $class::fromRequest(Request::create('/', 'GET', [
            'program_studi_id' => '4',
            'jurusan_id' => '2',
            'semester' => '6',
            'semester_tipe' => 'genap',
            'dosen_id' => '9',
            'search' => 'basis data',
            'per_page' => '25',
            'is_scheduled' => 'false',
        ]))->toArray();

        $this->assertSame([
            'program_studi_id' => 4,
            'jurusan_id' => 2,
            'semester' => 6,
            'semester_tipe' => 'genap',
            'dosen_id' => 9,
            'search' => 'basis data',
            'per_page' => '25',
            'is_scheduled' => false,
        ], $filters);
    }

    public function test_omits_scheduled_filter_when_request_does_not_include_it(): void
    {
        $class = KelasKuliahIndexFilters::class;
        $this->assertTrue(class_exists($class));

        $filters = $class::fromRequest(Request::create('/', 'GET'))->toArray();

        $this->assertNull($filters['is_scheduled']);
        $this->assertSame('all', $filters['per_page']);
    }
}
