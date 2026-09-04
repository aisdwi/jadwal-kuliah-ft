<?php

namespace App\Modules\Shared\Application\Service;

use Illuminate\Database\Eloquent\Builder;

final class AcademicScopeQuery
{
    public static function applyToMataKuliahQuery(Builder $query, array $scope): Builder
    {
        if (!empty($scope['restrict_by_program_studi']) && !empty($scope['program_studi_id'])) {
            return $query->where('program_studi_id', $scope['program_studi_id']);
        }

        if (!empty($scope['restrict_by_jurusan']) && !empty($scope['jurusan_id'])) {
            return $query->where('jurusan_id', $scope['jurusan_id']);
        }

        return $query;
    }

    public static function applyToKelasKuliahQuery(Builder $query, array $scope, string $relation = 'matakuliah'): Builder
    {
        if (empty($scope['is_restricted']) && empty($scope['restrict_by_jurusan'])) {
            return $query;
        }

        return $query->whereHas($relation, function (Builder $relationQuery) use ($scope) {
            self::applyToMataKuliahQuery($relationQuery, $scope);
        });
    }
}
