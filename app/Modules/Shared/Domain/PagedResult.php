<?php

namespace App\Modules\Shared\Domain;

final class PagedResult
{
    public function __construct(
        public readonly array   $items,
        public readonly ?int    $total       = null,
        public readonly ?int    $currentPage = null,
        public readonly ?int    $perPage     = null,
        public readonly ?int    $lastPage    = null,
    ) {}

    public function isPaginated(): bool
    {
        return $this->total !== null;
    }
}
