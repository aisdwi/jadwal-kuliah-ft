<?php

namespace App\Modules\KelasKuliah\Application\Port;

interface KelasImporterPort
{
    public function import(mixed $file): void;
}
