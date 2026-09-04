<?php

namespace App\Modules\Shared\Presentation\Http\Support;

use Illuminate\Support\Collection;

final class NestedPagedResponseFormatter
{
    private function __construct(
        private readonly mixed $result,
        private readonly mixed $formatItem,
        private readonly bool $mapUnknownResult,
        private readonly bool $includePaginationBounds,
    ) {}

    public static function format(
        mixed $result,
        callable $formatItem,
        bool $mapUnknownResult = false,
        bool $includePaginationBounds = true,
    ): array {
        return (new self($result, $formatItem, $mapUnknownResult, $includePaginationBounds))->formatResult();
    }

    private function formatResult(): array
    {
        if (is_array($this->result) && array_key_exists('data', $this->result) && array_key_exists('pagination', $this->result)) {
            return $this->formatExistingNested();
        }

        if (is_array($this->result) || $this->result instanceof Collection) {
            return $this->formatItems();
        }

        return is_object($this->result) && method_exists($this->result, 'items')
            ? $this->formatPaginator()
            : $this->formatFallback();
    }

    private function formatExistingNested(): array
    {
        return [
            'data' => $this->mapItems($this->result['data']),
            'pagination' => $this->result['pagination'],
        ];
    }

    private function formatItems(): array
    {
        $items = is_array($this->result) ? $this->result : $this->result->all();

        return ['data' => $this->mapItems($items)];
    }

    private function formatPaginator(): array
    {
        return [
            'data' => $this->mapItems($this->result->items()),
            'pagination' => $this->paginationMetadata(),
        ];
    }

    private function paginationMetadata(): array
    {
        $pagination = [
            'per_page' => $this->result->perPage(),
            'current_page' => $this->result->currentPage(),
            'total' => $this->result->total(),
            'last_page' => $this->result->lastPage(),
        ];

        if ($this->includePaginationBounds) {
            $pagination['from'] = method_exists($this->result, 'firstItem') ? $this->result->firstItem() : null;
            $pagination['to'] = method_exists($this->result, 'lastItem') ? $this->result->lastItem() : null;
        }

        return $pagination;
    }

    private function formatFallback(): array
    {
        if (!$this->mapUnknownResult) {
            return ['data' => []];
        }

        return [
            'data' => $this->mapItems((array) $this->result),
            'pagination' => null,
        ];
    }

    private function mapItems(array $items): array
    {
        return array_map($this->formatItem, $items);
    }
}
