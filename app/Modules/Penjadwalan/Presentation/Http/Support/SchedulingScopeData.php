<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

use App\Modules\Shared\Application\Service\AcademicScope;
use Illuminate\Http\Request;

final class SchedulingScopeData
{
    public static function scope(Request $request): array
    {
        $user = $request->user();
        $scope = AcademicScope::fromUser($user);
        $scope['user_id'] = $user?->id;

        return $scope;
    }

    public static function programStudiId(array $scope): int
    {
        if (!empty($scope['restrict_by_jurusan'])) {
            return 0;
        }

        return (int) ($scope['program_studi_id'] ?? 0);
    }

    public static function destructiveProgramStudiId(array $scope): int
    {
        if (empty($scope['restrict_by_program_studi'])) {
            return 0;
        }

        return self::programStudiId($scope);
    }
}
