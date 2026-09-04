<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

class KelasKuliahResource
{
    private function __construct(private readonly mixed $model) {}

    public static function toArray($model): array
    {
        return (new self($model))->serialize();
    }

    private function serialize(): array
    {
        $model = $this->model;
        $latestJadwal = $this->latestJadwal();
        $programStudi = KelasKuliahProgramStudiPayload::from($model->matakuliah?->programStudi);

        return [
            'id' => $model->id,
            'dosen_id' => $model->dosen_id,
            'dosen' => KelasKuliahDosenPayload::from($model->dosen),
            'dosen_nama' => $model->dosen?->nama_lengkap,
            'dosens' => KelasKuliahTeachingTeamPayload::from($model),
            'matakuliah_id' => $model->matakuliah_id,
            'matakuliah' => KelasKuliahMataKuliahPayload::from($model->matakuliah),
            'matakuliah_nama' => $model->matakuliah?->nama_mk,
            'kode_mk' => $model->matakuliah?->kode_mk,
            'sks' => $model->matakuliah?->sks,
            'kelas_id' => $model->kelas_id,
            'kelas' => KelasKuliahKelasPayload::from($model->kelas),
            'kelas_nama' => $model->kelas?->nama_kelas,
            'semester' => $model->kelas?->semester,
            'program_studi_id' => $model->matakuliah?->program_studi_id,
            'program_studi' => $programStudi['nama_prodi'] ?? null,
            'jumlah_mahasiswa' => $model->jumlah_mahasiswa,
            'semester_tipe' => $model->semester_tipe ?? null,
            'slot_id' => $latestJadwal?->slot_id,
            'slot' => KelasKuliahSlotPayload::from($latestJadwal?->slot),
            'ruangan_id' => $latestJadwal?->ruangan_id,
            'ruangan' => KelasKuliahRuanganPayload::from($latestJadwal?->ruangan),
            'jadwal_origin' => $latestJadwal?->origin,
        ];
    }

    private function latestJadwal()
    {
        return $this->model->jadwals ? $this->model->jadwals->sortByDesc('id')->first() : null;
    }

}
