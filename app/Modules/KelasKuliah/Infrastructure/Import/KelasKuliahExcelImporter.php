<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

use App\Modules\KelasKuliah\Application\Port\KelasKuliahImporterPort;
use Maatwebsite\Excel\Facades\Excel;

class KelasKuliahExcelImporter implements KelasKuliahImporterPort
{
    public function import(mixed $file): void
    {
        Excel::import(new KelasKuliahImport, $file);
    }
}
