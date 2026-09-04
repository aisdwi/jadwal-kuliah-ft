<?php

namespace Tests\Unit\Domain\Iam\Policy;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test: PermissionPolicy
 *
 * Membuktikan bahwa role-matrix di-enforce dengan benar tanpa framework.
 * Pure PHP — Laravel kernel tidak diboot.
 *
 * Role IDs:
 *   999 = Super Admin
 *   0   = Admin Fakultas
 *   1   = Wakil Dekan I
 *   2   = Sub-Koordinator
 *   3   = Kaprodi
 *   4   = Admin Prodi
 *   5   = Dosen
 */
class PermissionPolicyTest extends TestCase
{
    // ── Struktur matrix ───────────────────────────────────────────────────────

    #[Test]
    public function matrix_containsAllExpectedPermissions(): void
    {
        $keys = array_keys(PermissionPolicy::matrix());

        $expected = [
            PermissionPolicy::JADWAL_VIEW,
            PermissionPolicy::JADWAL_GENERATE,
            PermissionPolicy::JADWAL_MANUAL,
            PermissionPolicy::JADWAL_DELETE,
            PermissionPolicy::JADWAL_EXPORT,
            PermissionPolicy::MASTER_VIEW,
            PermissionPolicy::MASTER_WRITE,
            PermissionPolicy::USER_MANAGE,
        ];

        foreach ($expected as $perm) {
            self::assertContains($perm, $keys, "Permission '{$perm}' harus ada di matrix.");
        }
    }

    // ── Super Admin (999) — boleh semua ───────────────────────────────────────

    #[Test]
    public function superAdmin_canDoEverything(): void
    {
        foreach (array_keys(PermissionPolicy::matrix()) as $perm) {
            self::assertTrue(
                PermissionPolicy::can(999, $perm),
                "Super Admin (999) harus bisa '{$perm}'."
            );
        }
    }

    // ── Dosen (5) — hanya view dan export ────────────────────────────────────

    #[Test]
    public function dosen_canView(): void
    {
        self::assertTrue(PermissionPolicy::can(5, PermissionPolicy::JADWAL_VIEW));
        self::assertTrue(PermissionPolicy::can(5, PermissionPolicy::MASTER_VIEW));
        self::assertTrue(PermissionPolicy::can(5, PermissionPolicy::JADWAL_EXPORT));
    }

    #[Test]
    public function dosen_cannotWrite(): void
    {
        self::assertFalse(PermissionPolicy::can(5, PermissionPolicy::JADWAL_GENERATE));
        self::assertFalse(PermissionPolicy::can(5, PermissionPolicy::JADWAL_MANUAL));
        self::assertFalse(PermissionPolicy::can(5, PermissionPolicy::JADWAL_DELETE));
        self::assertFalse(PermissionPolicy::can(5, PermissionPolicy::MASTER_WRITE));
        self::assertFalse(PermissionPolicy::can(5, PermissionPolicy::USER_MANAGE));
    }

    // ── Wakil Dekan I (1) — view + export saja ───────────────────────────────

    #[Test]
    public function wakilDekan_canViewExport_butNotGenerate(): void
    {
        self::assertTrue(PermissionPolicy::can(1,  PermissionPolicy::JADWAL_VIEW));
        self::assertTrue(PermissionPolicy::can(1,  PermissionPolicy::JADWAL_EXPORT));
        self::assertFalse(PermissionPolicy::can(1, PermissionPolicy::JADWAL_GENERATE));
        self::assertFalse(PermissionPolicy::can(1, PermissionPolicy::MASTER_WRITE));
        self::assertFalse(PermissionPolicy::can(1, PermissionPolicy::USER_MANAGE));
    }

    // ── Kaprodi (3) — write + generate prodi sendiri ─────────────────────────

    #[Test]
    public function kaprodi_canGenerateAndWrite(): void
    {
        self::assertTrue(PermissionPolicy::can(3, PermissionPolicy::JADWAL_GENERATE));
        self::assertTrue(PermissionPolicy::can(3, PermissionPolicy::JADWAL_MANUAL));
        self::assertTrue(PermissionPolicy::can(3, PermissionPolicy::JADWAL_DELETE));
        self::assertTrue(PermissionPolicy::can(3, PermissionPolicy::MASTER_WRITE));
    }

    #[Test]
    public function kaprodi_cannotManageUsers(): void
    {
        self::assertFalse(PermissionPolicy::can(3, PermissionPolicy::USER_MANAGE));
    }

    // ── User Manage hanya admin ───────────────────────────────────────────────

    #[Test]
    public function userManage_onlyForSuperAdminAndAdminFakultas(): void
    {
        self::assertTrue(PermissionPolicy::can(999, PermissionPolicy::USER_MANAGE));
        self::assertTrue(PermissionPolicy::can(0,   PermissionPolicy::USER_MANAGE));

        // Semua role lain tidak boleh
        foreach ([1, 2, 3, 4, 5, 6, 7] as $roleId) {
            self::assertFalse(
                PermissionPolicy::can($roleId, PermissionPolicy::USER_MANAGE),
                "Role {$roleId} tidak boleh user.manage."
            );
        }
    }

    // ── Permission tidak dikenal ──────────────────────────────────────────────

    #[Test]
    public function can_returnsFalse_forUnknownPermission(): void
    {
        self::assertFalse(PermissionPolicy::can(999, 'nonexistent.permission'));
        self::assertFalse(PermissionPolicy::can(0,   'another.unknown'));
    }

    // ── Admin Prodi (4) ───────────────────────────────────────────────────────

    #[Test]
    public function adminProdi_canWriteAndGenerate_butNotManageUsers(): void
    {
        self::assertTrue(PermissionPolicy::can(4,  PermissionPolicy::JADWAL_GENERATE));
        self::assertTrue(PermissionPolicy::can(4,  PermissionPolicy::MASTER_WRITE));
        self::assertFalse(PermissionPolicy::can(4, PermissionPolicy::USER_MANAGE));
    }

    #[Test]
    public function ketuaJurusanAndKoordinatorProgramStudi_canView_butCannotWrite(): void
    {
        foreach ([6, 7] as $roleId) {
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_VIEW));
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_EXPORT));
            self::assertTrue(PermissionPolicy::can($roleId, PermissionPolicy::MASTER_VIEW));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_GENERATE));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_MANUAL));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::JADWAL_DELETE));
            self::assertFalse(PermissionPolicy::can($roleId, PermissionPolicy::MASTER_WRITE));
        }
    }
}
