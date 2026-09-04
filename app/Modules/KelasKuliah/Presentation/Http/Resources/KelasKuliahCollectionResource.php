<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

final class KelasKuliahCollectionResource
{
    public static function toArray($pagedResult): array
    {
        return PagedResponseFormatter::format($pagedResult, [KelasKuliahResource::class, 'toArray']);
    }
}
