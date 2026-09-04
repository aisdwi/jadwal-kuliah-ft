<?php

namespace App\Modules\Shared\Application\Port;

interface HashServiceInterface
{
    public function make(string $value): string;

    public function check(string $value, string $hashedValue): bool;
}
