<?php

namespace App\Modules\Shared\Application\Port;

interface ActivityLogRepositoryPort
{
    public function insert(array $data): void;
}
