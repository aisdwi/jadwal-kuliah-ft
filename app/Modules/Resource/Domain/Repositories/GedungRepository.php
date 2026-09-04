<?php

namespace App\Modules\Resource\Domain\Repositories;

use Illuminate\Support\Collection;

interface GedungRepository
{
    public function all(): Collection;
}
