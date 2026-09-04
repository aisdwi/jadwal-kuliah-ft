<?php

namespace Tests\Unit\Presentation;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class PagedResponseFormatterTest extends TestCase
{
    public function test_format_nested_returns_collection_items_without_pagination(): void
    {
        $result = PagedResponseFormatter::formatNested(
            collect([(object) ['id' => 1], (object) ['id' => 2]]),
            fn ($item) => ['id' => $item->id],
        );

        $this->assertSame([
            'data' => [
                ['id' => 1],
                ['id' => 2],
            ],
        ], $result);
    }

    public function test_format_nested_returns_paginator_metadata_under_pagination_key(): void
    {
        $paginator = new LengthAwarePaginator(
            [(object) ['id' => 1], (object) ['id' => 2]],
            total: 5,
            perPage: 2,
            currentPage: 2,
        );

        $result = PagedResponseFormatter::formatNested($paginator, fn ($item) => ['id' => $item->id]);

        $this->assertSame([
            'data' => [
                ['id' => 1],
                ['id' => 2],
            ],
            'pagination' => [
                'per_page' => 2,
                'current_page' => 2,
                'total' => 5,
                'last_page' => 3,
                'from' => 3,
                'to' => 4,
            ],
        ], $result);
    }

    public function test_format_nested_preserves_existing_pagination_payload(): void
    {
        $result = PagedResponseFormatter::formatNested([
            'data' => [['id' => 1]],
            'pagination' => ['total' => 1],
        ], fn ($item) => ['id' => $item['id']]);

        $this->assertSame([
            'data' => [
                ['id' => 1],
            ],
            'pagination' => ['total' => 1],
        ], $result);
    }
}
