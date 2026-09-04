<?php

namespace App\Modules\Iam\Application\Service;

class PermissionPolicy
{
    const JADWAL_VIEW     = 'jadwal.view';
    const JADWAL_GENERATE = 'jadwal.generate';
    const JADWAL_MANUAL   = 'jadwal.manual';
    const JADWAL_DELETE   = 'jadwal.delete';
    const JADWAL_EXPORT   = 'jadwal.export';
    const MASTER_VIEW     = 'master.view';
    const MASTER_WRITE    = 'master.write';
    const USER_MANAGE     = 'user.manage';

    private static array $permissions = [
        // Super Admin
        999 => [
            self::JADWAL_VIEW, self::JADWAL_GENERATE, self::JADWAL_MANUAL,
            self::JADWAL_DELETE, self::JADWAL_EXPORT,
            self::MASTER_VIEW, self::MASTER_WRITE, self::USER_MANAGE,
        ],
        // Admin Fakultas
        0 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT,
            self::MASTER_VIEW, self::MASTER_WRITE, self::USER_MANAGE,
        ],
        // Admin Jurusan
        3 => [
            self::JADWAL_VIEW, self::JADWAL_GENERATE, self::JADWAL_MANUAL,
            self::JADWAL_DELETE, self::JADWAL_EXPORT,
            self::MASTER_VIEW, self::MASTER_WRITE,
        ],
        // Admin Prodi legacy, diperlakukan sama seperti Admin Jurusan
        4 => [
            self::JADWAL_VIEW, self::JADWAL_GENERATE, self::JADWAL_MANUAL,
            self::JADWAL_DELETE, self::JADWAL_EXPORT,
            self::MASTER_VIEW, self::MASTER_WRITE,
        ],
        // Wakil Dekan I Bidang Akademik
        1 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT, self::MASTER_VIEW,
        ],
        // Sub-Koordinator Bidang Akademik
        2 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT, self::MASTER_VIEW,
        ],
        // Dosen
        5 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT, self::MASTER_VIEW,
        ],
        // Ketua Jurusan
        6 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT, self::MASTER_VIEW,
        ],
        // Koordinator Program Studi
        7 => [
            self::JADWAL_VIEW, self::JADWAL_EXPORT, self::MASTER_VIEW,
        ],
    ];

    public static function matrix(): array
    {
        $matrix = [];

        foreach (self::$permissions as $roleId => $abilities) {
            foreach ($abilities as $ability) {
                $matrix[$ability][] = $roleId;
            }
        }

        return $matrix;
    }

    public static function can(int $roleId, string $ability): bool
    {
        $allowed = self::$permissions[$roleId] ?? [];
        return in_array($ability, $allowed, true);
    }
}
