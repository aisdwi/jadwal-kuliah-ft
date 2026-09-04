<?php

namespace Tests\Unit\Application\Iam;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use App\Modules\Iam\Application\Support\RoleName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RoleScopePolicyTest extends TestCase
{
    #[Test]
    public function ketua_jurusan_and_koordinator_program_studi_default_to_own_jurusan_without_hard_scope(): void
    {
        self::assertFalse(RoleName::isJurusanScoped('Ketua Jurusan'));
        self::assertFalse(RoleName::isJurusanScoped('Koordinator Program Studi'));
        self::assertTrue(RoleName::defaultsToOwnJurusan('Ketua Jurusan'));
        self::assertTrue(RoleName::defaultsToOwnJurusan('Koordinator Program Studi'));
        self::assertTrue(RoleName::isAcademicObserver('Ketua Jurusan'));
        self::assertTrue(RoleName::isAcademicObserver('Koordinator Program Studi'));
        self::assertSame('Ketua Jurusan', RoleName::normalize('Kajur'));
        self::assertSame('Koordinator Program Studi', RoleName::normalize('Kaprodi'));
    }

    #[Test]
    public function observer_academic_roles_can_view_and_export_without_write_access(): void
    {
        foreach ([1, 6, 7] as $roleId) {
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_VIEW));
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_EXPORT));
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::MASTER_VIEW));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_GENERATE));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_MANUAL));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_DELETE));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::MASTER_WRITE));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::USER_MANAGE));
        }
    }
}
