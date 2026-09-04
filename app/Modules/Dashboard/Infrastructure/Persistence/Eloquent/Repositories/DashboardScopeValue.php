<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

final class DashboardScopeValue
{
    public static function jurusanId(array $scope): ?int
    {
        $value = !empty($scope['restrict_by_jurusan'])
            ? ($scope['jurusan_id'] ?? null)
            : ($scope['default_jurusan_id'] ?? null);

        return $value === null ? null : (int) $value;
    }

    public static function programStudiId(array $scope): ?int
    {
        $value = !empty($scope['restrict_by_program_studi'])
            ? ($scope['program_studi_id'] ?? null)
            : ($scope['default_program_studi_id'] ?? null);

        return $value === null ? null : (int) $value;
    }
}
