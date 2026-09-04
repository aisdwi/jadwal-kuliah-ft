<?php

namespace App\Modules\Laporan\Infrastructure\Export;

use App\Modules\Laporan\Application\Port\ExcelExporterPort;
use Maatwebsite\Excel\Facades\Excel;

final class MaatwebsiteExcelExporter implements ExcelExporterPort
{
    public function exportJadwal(
        array   $jadwals,
        ?string $programStudiNama = null,
        ?int    $semester         = null,
    ): string {
        $title = $this->buildTitle($programStudiNama, $semester);
        $sheet = new JadwalExcelSheet($jadwals, $title);
        return Excel::raw($sheet, \Maatwebsite\Excel\Excel::XLSX);
    }

    private function buildTitle(?string $prodi, ?int $semester): string
    {
        $title = $prodi ?? 'Jadwal Kuliah';
        return $semester !== null ? "{$title} - Semester {$semester}" : $title;
    }
}
