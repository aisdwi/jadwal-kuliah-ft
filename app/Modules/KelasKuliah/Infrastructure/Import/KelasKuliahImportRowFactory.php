<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

final class KelasKuliahImportRowFactory
{
    public static function fromArray(array $data): KelasKuliahImportRow
    {
        $kelasRaw = self::stringValue($data, 'kelas');
        $kelas = KelasKuliahClassLabel::from($kelasRaw);

        return new KelasKuliahImportRow(
            kodeMk: self::stringValue($data, 'kode_mk'),
            kelasRaw: $kelasRaw,
            namaKelas: $kelas->name,
            semester: $kelas->semester,
            jumlahMahasiswa: self::intValue($data, 'jumlah_mahasiswa'),
            pengampu: self::stringValue($data, 'dosen_pengampu'),
            programStudi: self::stringValue($data, 'program_studi'),
            jurusan: self::stringValue($data, 'jurusan'),
        );
    }

    private static function stringValue(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    private static function intValue(array $data, string $key): int
    {
        return (int) ($data[$key] ?? 0);
    }
}
