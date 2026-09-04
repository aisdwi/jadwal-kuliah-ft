<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\MataKuliahModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use Illuminate\Support\Facades\DB;

final class DashboardStatsQuery
{
    public function getStats(array $userScope): array
    {
        return [
            'totalDosen' => $this->totalDosen($userScope),
            'totalRuangan' => $this->totalRuangan($userScope),
            'totalMataKuliah' => $this->totalMataKuliah($userScope),
            'totalKelasKuliah' => $this->totalKelasKuliah($userScope),
            'totalKelasKuliahTerjadwal' => $this->totalKelasKuliahTerjadwal($userScope),
            'totalMataKuliahTerjadwal' => $this->totalMataKuliahTerjadwal($userScope),
        ];
    }

    private function totalDosen(array $userScope): int
    {
        $jurusanId = DashboardScopeValue::jurusanId($userScope);

        return DosenModel::query()
            ->when($jurusanId !== null, fn ($query) => $query->where('jurusan_id', $jurusanId))
            ->count();
    }

    private function totalRuangan(array $userScope): int
    {
        $jurusanId = DashboardScopeValue::jurusanId($userScope);

        return $jurusanId === null
            ? RuanganModel::count()
            : DB::table('jurusan_ruangan')->where('jurusan_id', $jurusanId)->count();
    }

    private function totalMataKuliah(array $userScope): int
    {
        return DashboardScopedQuery::mataKuliah(MataKuliahModel::query(), $userScope)->count();
    }

    private function totalKelasKuliah(array $userScope): int
    {
        return DashboardScopedQuery::kelasKuliah(KelasKuliahModel::query(), $userScope)->count();
    }

    private function totalKelasKuliahTerjadwal(array $userScope): int
    {
        return DashboardScopedQuery::kelasKuliah(KelasKuliahModel::query(), $userScope)
            ->whereHas('jadwals')
            ->count();
    }

    private function totalMataKuliahTerjadwal(array $userScope): int
    {
        return DashboardScopedQuery::kelasKuliah(KelasKuliahModel::query(), $userScope)
            ->whereHas('jadwals')
            ->distinct('matakuliah_id')
            ->count('matakuliah_id');
    }
}
