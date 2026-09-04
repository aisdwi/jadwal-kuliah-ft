<?php

namespace App\Modules\Laporan\Infrastructure\Export;

final class JadwalExcelRowMapper
{
    private const PLACEHOLDER = '-';
    private const COLUMNS = [
        [['hari_nama', 'hariNama'], 'str'],
        [['waktu_pukul', 'waktuPukul'], 'str'],
        [['matakuliah_nama', 'matakuliahNama'], 'str'],
        [['kode_mk', 'kodeMk'], 'str'],
        [['sks'], 'int'],
        [['kelas_nama', 'kelasNama'], 'str'],
        [['semester'], 'int'],
        [['dosen_nama', 'dosenNama'], 'str'],
        [['ruangan_nama', 'ruanganNama'], 'str'],
        [['kapasitas_ruangan', 'kapasitasRuangan'], 'int'],
        [['jumlah_mahasiswa', 'jumlahMahasiswa'], 'int'],
        [['origin'], 'origin'],
    ];

    private function __construct(
        private readonly int $no,
        private readonly object $jadwal,
    ) {}

    public static function map(array $jadwals): array
    {
        $rows = [];
        foreach ($jadwals as $no => $jadwal) {
            $rows[] = (new self($no + 1, $jadwal))->buildRow();
        }
        return $rows;
    }

    private function buildRow(): array
    {
        $row = [$this->no];

        foreach (self::COLUMNS as [$fields, $formatter]) {
            $row[] = $this->formatColumn($formatter, $this->firstAvailable($fields));
        }

        return $row;
    }

    private function firstAvailable(array $fields): mixed
    {
        foreach ($fields as $field) {
            if (isset($this->jadwal->{$field})) {
                return $this->jadwal->{$field};
            }
        }

        return null;
    }

    private function formatColumn(string $formatter, mixed $value): string
    {
        return match ($formatter) {
            'int' => $this->int($value),
            'origin' => $this->origin((string) ($value ?? 'generate')),
            default => $this->str($value),
        };
    }

    private function str(mixed $v): string
    {
        return $v === null ? self::PLACEHOLDER : (string) $v;
    }

    private function int(mixed $v): string
    {
        return $v === null ? self::PLACEHOLDER : (string) ((int) $v);
    }

    private function origin(string $origin): string
    {
        return $origin === 'manual' ? 'Manual' : 'Generate';
    }
}
