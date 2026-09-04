<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Presentation\Http\Support\SchedulingScopeData;
use PHPUnit\Framework\TestCase;

class SchedulingRequestDataTest extends TestCase
{
    public function test_destructive_program_studi_scope_ignores_default_prodi_for_jurusan_scope(): void
    {
        $scope = [
            'program_studi_id' => 3,
            'restrict_by_jurusan' => true,
            'restrict_by_program_studi' => false,
        ];

        $this->assertSame(0, SchedulingScopeData::destructiveProgramStudiId($scope));
    }

    public function test_program_studi_scope_ignores_default_prodi_for_jurusan_scope(): void
    {
        $scope = [
            'program_studi_id' => 3,
            'restrict_by_jurusan' => true,
            'restrict_by_program_studi' => false,
        ];

        $this->assertSame(0, SchedulingScopeData::programStudiId($scope));
    }

    public function test_destructive_program_studi_scope_uses_prodi_only_when_restricted_by_prodi(): void
    {
        $scope = [
            'program_studi_id' => 3,
            'restrict_by_jurusan' => false,
            'restrict_by_program_studi' => true,
        ];

        $this->assertSame(3, SchedulingScopeData::destructiveProgramStudiId($scope));
    }
}
