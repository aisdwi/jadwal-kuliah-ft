<?php

namespace App\Modules\MasterAkademik\Application\Port;

interface MataKuliahImporterPort
{
    public function import(mixed $file): void;
}
