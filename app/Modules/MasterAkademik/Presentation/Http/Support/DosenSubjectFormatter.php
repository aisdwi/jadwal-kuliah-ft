<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Support;

final class DosenSubjectFormatter
{
    public static function format(object $dosen): string
    {
        $inisial = trim((string) ($dosen->inisial ?? ''));
        $nama = trim((string) ($dosen->nama_lengkap ?? ''));
        $label = implode(' - ', array_values(array_filter([$inisial, $nama])));

        return $label !== '' ? $label : 'Dosen';
    }
}
