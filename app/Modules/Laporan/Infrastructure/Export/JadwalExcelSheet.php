<?php

namespace App\Modules\Laporan\Infrastructure\Export;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class JadwalExcelSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly array  $jadwals,
        private readonly string $sheetTitle,
    ) {}

    public function title(): string
    {
        return substr($this->sheetTitle, 0, 31);
    }

    public function headings(): array
    {
        return JadwalExcelHeadings::COLUMNS;
    }

    public function array(): array
    {
        return JadwalExcelRowMapper::map($this->jadwals);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
