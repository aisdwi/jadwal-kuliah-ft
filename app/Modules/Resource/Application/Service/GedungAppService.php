<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\Resource\Domain\Repositories\GedungRepository;
use Illuminate\Support\Collection;

class GedungAppService
{
    public function __construct(
        private readonly GedungRepository $gedungRepository,
    ) {}

    public function list(): Collection
    {
        return $this->gedungRepository->all();
    }
}
