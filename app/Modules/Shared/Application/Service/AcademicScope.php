<?php

namespace App\Modules\Shared\Application\Service;

use App\Modules\Iam\Application\Support\RoleName;

class AcademicScope
{
    public static function fromUser(?object $user): array
    {
        $rawRoleName = (string) ($user?->role?->role ?? '');
        $roleName = RoleName::normalize($rawRoleName);

        $isJurusanRole = RoleName::isJurusanScoped($rawRoleName);
        $defaultsToOwnJurusan = RoleName::defaultsToOwnJurusan($rawRoleName);
        $defaultsToOwnProgramStudi = $roleName === 'Koordinator Program Studi';
        $programStudiId = empty($user?->program_studi_id) ? null : (int) $user->program_studi_id;
        $jurusanId = AcademicScopeJurusanResolver::resolve($user, $programStudiId);

        return AcademicScopePayload::make(
            $roleName,
            $jurusanId,
            $programStudiId,
            $defaultsToOwnJurusan,
            $defaultsToOwnProgramStudi,
            $isJurusanRole,
        );
    }

}
