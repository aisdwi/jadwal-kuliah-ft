<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

use App\Modules\KelasKuliah\Application\Port\KelasImporterPort;
use Maatwebsite\Excel\Facades\Excel;

class KelasExcelImporter implements KelasImporterPort
{
    public function import(mixed $file): void
    {
        Excel::import(new KelasImport, $file);
    }
}
