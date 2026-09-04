<?php

namespace App\Modules\Shared\Application\Service;

use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Support\Collection;

final class BulkDeleteResource
{
    public static function delete(mixed $result, callable $deleteById, ?callable $shouldDelete = null): int
    {
        $deleted = 0;

        foreach (self::items($result) as $item) {
            $id = self::id($item);
            if ($id === null) {
                continue;
            }

            if ($shouldDelete !== null && ! $shouldDelete($item, $id)) {
                continue;
            }

            $deleteById($id);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * @return iterable<mixed>
     */
    private static function items(mixed $result): iterable
    {
        if ($result instanceof PagedResult) {
            return $result->items;
        }

        if ($result instanceof Collection) {
            return $result->all();
        }

        if (is_array($result)) {
            return $result;
        }

        if (is_iterable($result)) {
            return $result;
        }

        return [];
    }

    private static function id(mixed $item): ?int
    {
        $id = is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);

        return $id === null ? null : (int) $id;
    }
}
