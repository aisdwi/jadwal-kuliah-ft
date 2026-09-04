<?php

namespace App\Modules\Shared\Presentation\Http\Support;

final class PagedResponseFormatter
{
    /**
     * @param callable(mixed): array<string, mixed> $formatItem
     * @return array<string, mixed>
     */
    public static function format(mixed $result, callable $formatItem): array
    {
        return FlatPagedResponseFormatter::format($result, $formatItem);
    }

    /**
     * @param callable(mixed): array<string, mixed> $formatItem
     * @return array<string, mixed>
     */
    public static function formatNested(
        mixed $result,
        callable $formatItem,
        bool $mapUnknownResult = false,
        bool $includePaginationBounds = true,
    ): array {
        return NestedPagedResponseFormatter::format($result, $formatItem, $mapUnknownResult, $includePaginationBounds);
    }
}
