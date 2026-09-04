<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

final class JadwalCourseLabel
{
    public static function from(array $data): string
    {
        $kode = trim((string) data_get($data, 'kelas_kuliah.matakuliah.kode_mk', ''));
        $mataKuliah = trim((string) data_get($data, 'kelas_kuliah.matakuliah.nama_mk', ''));
        $kelas = trim((string) data_get($data, 'kelas_kuliah.kelas.nama_kelas', ''));
        $label = implode(' - ', array_values(array_filter([$kode, $mataKuliah])));

        return $kelas === '' ? $label : trim($label . ' / ' . $kelas, ' /');
    }
}
