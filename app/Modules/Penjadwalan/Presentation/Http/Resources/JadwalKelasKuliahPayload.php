<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalKelasKuliahPayload
{
    private function __construct(private readonly array $kelasKuliah) {}

    public static function from(?array $kelasKuliah): ?array
    {
        return $kelasKuliah ? (new self($kelasKuliah))->toArray() : null;
    }

    private function toArray(): array
    {
        return [
            'id' => $this->kelasKuliah['id'] ?? null,
            'dosen_id' => $this->kelasKuliah['dosen_id'] ?? null,
            'matakuliah_id' => $this->kelasKuliah['matakuliah_id'] ?? null,
            'kelas_id' => $this->kelasKuliah['kelas_id'] ?? null,
            'jumlah_mahasiswa' => $this->kelasKuliah['jumlah_mahasiswa'] ?? null,
            'dosen' => JadwalDosenPayload::from($this->kelasKuliah['dosen'] ?? null),
            'matakuliah' => JadwalMataKuliahPayload::from($this->kelasKuliah['matakuliah'] ?? null),
            'kelas' => JadwalKelasPayload::from($this->kelasKuliah['kelas'] ?? null),
        ];
    }
}
