<?php

namespace App\Modules\Laporan\Application\Port;

interface ExcelExporterPort
{
    public function exportJadwal(
        array   $jadwals,
        ?string $programStudiNama = null,
        ?int    $semester         = null,
    ): string;
}
