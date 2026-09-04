<?php

namespace App\Modules\Shared\Presentation\Http\Support;

use App\Modules\Shared\Domain\PagedResult;

final class FlatPagedResponseFormatter
{
    private function __construct(
        private readonly mixed $result,
        private readonly mixed $formatItem,
    ) {}

    public static function format(mixed $result, callable $formatItem): array
    {
        return (new self($result, $formatItem))->formatResult();
    }

    private function formatResult(): array
    {
        if (is_object($this->result) && method_exists($this->result, 'isPaginated')) {
            return $this->formatPagedResult();
        }

        if (is_object($this->result) && method_exists($this->result, 'items')) {
            return $this->formatPaginator();
        }

        return $this->formatPlainItems();
    }

    private function formatPagedResult(): array
    {
        $items = $this->mapItems($this->result->items);
        if (!$this->result->isPaginated()) {
            return ['data' => $items];
        }

        return [
            'data' => $items,
            'total' => $this->result->total,
            'current_page' => $this->result->currentPage,
            'per_page' => $this->result->perPage,
            'last_page' => $this->result->lastPage,
        ];
    }

    private function formatPaginator(): array
    {
        return [
            'data' => $this->mapItems($this->result->items()),
            'total' => $this->result->total(),
            'current_page' => $this->result->currentPage(),
            'per_page' => $this->result->perPage(),
            'last_page' => $this->result->lastPage(),
        ];
    }

    private function formatPlainItems(): array
    {
        $items = $this->result instanceof PagedResult ? $this->result->items : collect($this->result)->all();

        return ['data' => $this->mapItems($items)];
    }

    private function mapItems(array $items): array
    {
        return array_map($this->formatItem, $items);
    }
}
