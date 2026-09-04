<?php

namespace App\Modules\Iam\Application\Support;

final class RoleName
{
    private const ADMIN_JURUSAN = 'Admin Jurusan';
    private const ADMIN_PRODI = 'Admin Prodi';
    private const KAJUR = 'Kajur';
    private const KAPRODI = 'Kaprodi';
    private const KETUA_JURUSAN = 'Ketua Jurusan';
    private const KOORDINATOR_PROGRAM_STUDI = 'Koordinator Program Studi';

    private const LEGACY_TO_CANONICAL = [
        self::ADMIN_PRODI => self::ADMIN_JURUSAN,
        self::KAJUR => self::KETUA_JURUSAN,
        self::KAPRODI => self::KOORDINATOR_PROGRAM_STUDI,
    ];

    private const UNRESTRICTED_ROLES = [
        'Super Admin',
        'Admin Fakultas',
        'Super User',
    ];

    private const JURUSAN_SCOPED_ROLES = [
        self::ADMIN_JURUSAN,
        self::ADMIN_PRODI,
    ];

    private const OWN_JURUSAN_DEFAULT_ROLES = [
        self::ADMIN_JURUSAN,
        self::ADMIN_PRODI,
        self::KAJUR,
        self::KAPRODI,
        self::KETUA_JURUSAN,
        self::KOORDINATOR_PROGRAM_STUDI,
    ];

    private const ACADEMIC_OBSERVER_ROLES = [
        self::KAJUR,
        self::KAPRODI,
        self::KETUA_JURUSAN,
        self::KOORDINATOR_PROGRAM_STUDI,
        'Wakil Dekan I Bidang Akademik',
        'Sub-Koordinator Bidang Akademik',
    ];

    public static function normalize(?string $roleName): string
    {
        $roleName = trim((string) $roleName);

        return self::LEGACY_TO_CANONICAL[$roleName] ?? $roleName;
    }

    public static function isUnrestricted(?string $roleName): bool
    {
        return in_array(self::normalize($roleName), self::UNRESTRICTED_ROLES, true);
    }

    public static function isJurusanScoped(?string $roleName): bool
    {
        return in_array(self::normalize($roleName), self::JURUSAN_SCOPED_ROLES, true);
    }

    public static function defaultsToOwnJurusan(?string $roleName): bool
    {
        return in_array(self::normalize($roleName), self::OWN_JURUSAN_DEFAULT_ROLES, true);
    }

    public static function isAcademicObserver(?string $roleName): bool
    {
        return in_array(self::normalize($roleName), self::ACADEMIC_OBSERVER_ROLES, true);
    }
}
