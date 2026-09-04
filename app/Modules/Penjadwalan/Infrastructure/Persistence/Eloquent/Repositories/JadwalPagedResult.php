<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories;

final class JadwalPagedResult
{
    public static function fromQuery($query, int|string $perPage): array
    {
        if ($perPage === 'all') {
            return $query->get()->toArray();
        }

        $paged = $query->paginate(max(1, (int) $perPage));

        return [
            'data' => $paged->items(),
            'pagination' => [
                'total' => $paged->total(),
                'current_page' => $paged->currentPage(),
                'per_page' => $paged->perPage(),
                'last_page' => $paged->lastPage(),
            ],
        ];
    }
}
