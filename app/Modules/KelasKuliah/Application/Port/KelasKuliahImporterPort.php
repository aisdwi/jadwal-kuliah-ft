<?php

namespace App\Modules\KelasKuliah\Application\Port;

interface KelasKuliahImporterPort
{
    public function import(mixed $file): void;
}
