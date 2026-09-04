<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Support;

final class KelasKuliahSubjectFormatter
{
    public static function format(object $kelasKuliah): string
    {
        $kode = trim((string) ($kelasKuliah->matakuliah?->kode_mk ?? ''));
        $mataKuliah = trim((string) ($kelasKuliah->matakuliah?->nama_mk ?? ''));
        $kelas = trim((string) ($kelasKuliah->kelas?->nama_kelas ?? ''));

        $label = implode(' - ', array_values(array_filter([$kode, $mataKuliah])));

        if ($kelas !== '') {
            $label = trim($label . ' / ' . $kelas, ' /');
        }

        return $label !== '' ? $label : 'Kelas Kuliah';
    }
}
