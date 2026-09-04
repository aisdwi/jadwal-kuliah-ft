<?php

namespace App\Modules\MasterAkademik\Application\Port;

interface DosenImporterPort
{
    public function import(mixed $file): void;
}
