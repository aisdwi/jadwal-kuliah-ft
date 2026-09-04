<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalKelasPayload
{
    public static function from(?array $kelas): ?array
    {
        return $kelas ? [
            'id' => $kelas['id'] ?? null,
            'nama_kelas' => $kelas['nama_kelas'] ?? null,
            'semester' => $kelas['semester'] ?? null,
            'program_studi_id' => $kelas['program_studi_id'] ?? null,
            'jurusan_id' => $kelas['jurusan_id'] ?? null,
            'program_studi' => JadwalProgramStudiPayload::from($kelas['program_studi'] ?? null),
        ] : null;
    }
}
