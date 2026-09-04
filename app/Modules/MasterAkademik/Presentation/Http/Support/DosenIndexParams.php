<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Support;

use Illuminate\Http\Request;

final class DosenIndexParams
{
    private function __construct(
        public readonly ?string $search,
        public readonly ?int $jurusanId,
        public readonly bool $filterByJurusan,
        public readonly int|string $perPage,
        public readonly ?int $excludeJurusanId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $filterByJurusan = !$request->boolean('unscoped');

        return new self(
            search: $request->string('search', null)->toString() ?: null,
            jurusanId: $filterByJurusan && $request->filled('jurusan_id') ? (int) $request->jurusan_id : null,
            filterByJurusan: $filterByJurusan,
            perPage: DosenIndexRequestValue::perPage($request),
            excludeJurusanId: DosenIndexRequestValue::excludeJurusanId($request, $filterByJurusan),
        );
    }
}
