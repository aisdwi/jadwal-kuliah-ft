<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;

final class DashboardChartQuery
{
    public function getChartData(array $userScope): array
    {
        $roleName = RoleName::normalize($userScope['role_name'] ?? null);

        return $this->showsFacultyProgress($roleName)
            ? $this->jurusanProgressRows($userScope)
            : $this->programStudiProgressRows($userScope);
    }

    private function showsFacultyProgress(?string $roleName): bool
    {
        return RoleName::isUnrestricted($roleName)
            || in_array($roleName, ['Super Admin', 'Admin Fakultas', 'Wakil Dekan I Bidang Akademik', 'Sub-Koordinator Bidang Akademik'], true);
    }

    private function jurusanProgressRows(array $userScope): array
    {
        $jurusanId = DashboardScopeValue::jurusanId($userScope);

        return JurusanModel::query()
            ->when($jurusanId !== null, fn ($query) => $query->where('id', $jurusanId))
            ->get()
            ->map(fn ($jurusan): array => $this->jurusanProgressRow($jurusan, $userScope))
            ->filter(fn (array $item): bool => $item['total'] > 0)
            ->values()
            ->toArray();
    }

    private function jurusanProgressRow(object $jurusan, array $userScope): array
    {
        $terjadwal = $this->kelasKuliahCountForJurusan($jurusan->id, $userScope, true);
        $total = $this->kelasKuliahCountForJurusan($jurusan->id, $userScope);

        return $this->progressRow($jurusan->nama_jurusan ?? 'Unknown', $terjadwal, $total);
    }

    private function programStudiProgressRows(array $userScope): array
    {
        return $this->programStudiProgressQuery($userScope)
            ->get()
            ->map(fn ($prodi): array => $this->programStudiProgressRow($prodi, $userScope))
            ->filter(fn (array $item): bool => $item['total'] > 0)
            ->values()
            ->toArray();
    }

    private function programStudiProgressQuery(array $userScope)
    {
        $programStudiId = DashboardScopeValue::programStudiId($userScope);
        $jurusanId = DashboardScopeValue::jurusanId($userScope);

        return ProgramStudiModel::query()
            ->when($programStudiId !== null, fn ($query) => $query->where('id', $programStudiId))
            ->when($programStudiId === null && $jurusanId !== null, fn ($query) => $query->where('jurusan_id', $jurusanId));
    }

    private function programStudiProgressRow(object $prodi, array $userScope): array
    {
        $terjadwal = $this->kelasKuliahCountForProgramStudi($prodi->id, $userScope, true);
        $total = $this->kelasKuliahCountForProgramStudi($prodi->id, $userScope);

        return $this->progressRow($prodi->nama_prodi ?? 'Unknown', $terjadwal, $total);
    }

    private function kelasKuliahCountForJurusan(int $jurusanId, array $scope, bool $scheduledOnly = false): int
    {
        return $this->scopedKelasKuliahCount($scope, $scheduledOnly)
            ->whereHas('matakuliah.programStudi', fn ($query) => $query->where('jurusan_id', $jurusanId))
            ->count();
    }

    private function kelasKuliahCountForProgramStudi(int $programStudiId, array $scope, bool $scheduledOnly = false): int
    {
        return $this->scopedKelasKuliahCount($scope, $scheduledOnly)
            ->whereHas('matakuliah', fn ($query) => $query->where('program_studi_id', $programStudiId))
            ->count();
    }

    private function scopedKelasKuliahCount(array $scope, bool $scheduledOnly)
    {
        $query = KelasKuliahModel::query();
        DashboardSemesterScope::applyToKelasKuliah($query, $scope);

        return $scheduledOnly ? $query->whereHas('jadwals') : $query;
    }

    private function progressRow(string $name, int $terjadwal, int $total): array
    {
        return [
            'name' => $name,
            'filled' => $total > 0 ? round(($terjadwal / $total) * 100) : 0,
            'terjadwal' => $terjadwal,
            'total' => $total,
        ];
    }
}
