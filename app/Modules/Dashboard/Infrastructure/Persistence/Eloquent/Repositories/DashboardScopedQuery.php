<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

final class DashboardScopedQuery
{
    public static function mataKuliah($query, array $scope)
    {
        DashboardSemesterScope::applyToMataKuliah($query, $scope);

        $programStudiId = DashboardScopeValue::programStudiId($scope);
        if ($programStudiId !== null) {
            return $query->where('program_studi_id', $programStudiId);
        }

        $jurusanId = DashboardScopeValue::jurusanId($scope);
        if ($jurusanId !== null) {
            return $query->whereHas('programStudi', fn ($q) => $q->where('jurusan_id', $jurusanId));
        }

        return $query;
    }

    public static function kelasKuliah($query, array $scope)
    {
        DashboardSemesterScope::applyToKelasKuliah($query, $scope);

        $programStudiId = DashboardScopeValue::programStudiId($scope);
        if ($programStudiId !== null) {
            return $query->whereHas('matakuliah', fn ($q) => $q->where('program_studi_id', $programStudiId));
        }

        $jurusanId = DashboardScopeValue::jurusanId($scope);
        if ($jurusanId !== null) {
            return $query->whereHas('matakuliah.programStudi', fn ($q) => $q->where('jurusan_id', $jurusanId));
        }

        return $query;
    }

}
