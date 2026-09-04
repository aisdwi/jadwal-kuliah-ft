<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shared\Domain\PagedResult;

final class KelasKuliahPagedResultFactory
{
    public function make($query, mixed $perPage): PagedResult
    {
        if ($perPage === 'all') {
            return new PagedResult($query->get()->all());
        }

        $paged = $query->paginate(max(1, (int) $perPage));

        return new PagedResult(
            items: $paged->items(),
            total: $paged->total(),
            currentPage: $paged->currentPage(),
            perPage: $paged->perPage(),
            lastPage: $paged->lastPage(),
        );
    }
}
