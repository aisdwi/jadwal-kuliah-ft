<?php

namespace App\Modules\MasterAkademik\Infrastructure\Import;

use App\Modules\MasterAkademik\Application\Port\MataKuliahImporterPort;
use Maatwebsite\Excel\Facades\Excel;

class MataKuliahExcelImporter implements MataKuliahImporterPort
{
    public function import(mixed $file): void
    {
        Excel::import(new MatakuliahImport, $file);
    }
}
