<?php

namespace App\Modules\MasterAkademik\Infrastructure\Import;

use App\Modules\MasterAkademik\Application\Port\DosenImporterPort;
use Maatwebsite\Excel\Facades\Excel;

class DosenExcelImporter implements DosenImporterPort
{
    public function import(mixed $file): void
    {
        Excel::import(new DosenImport, $file);
    }
}
