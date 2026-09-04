<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Support;

use Illuminate\Http\Request;

final class DosenIndexRequestValue
{
    public static function perPage(Request $request): int|string
    {
        $input = $request->input('per_page', 10);

        return $input === 'all' ? 'all' : max(1, (int) $input);
    }

    public static function excludeJurusanId(Request $request, bool $filterByJurusan): ?int
    {
        $jurusanId = $request->user()?->jurusan_id;

        return $request->boolean('outside_jurusan') && !$filterByJurusan && $jurusanId
            ? (int) $jurusanId
            : null;
    }
}
