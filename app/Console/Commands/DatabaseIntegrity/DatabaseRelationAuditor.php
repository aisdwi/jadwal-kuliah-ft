<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DatabaseRelationAuditor
{
    public function __construct(private readonly DatabaseColumnDefinition $columns = new DatabaseColumnDefinition())
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function auditAll(): array
    {
        return array_map(
            fn (array $relation): array => $this->audit($relation),
            DatabaseIntegrityRules::relations()
        );
    }

    /**
     * @param array<string, string> $relation
     * @return array<string, mixed>
     */
    private function audit(array $relation): array
    {
        if (! $this->columnsExist($relation)) {
            return $this->skippedRelation($relation);
        }

        return $this->relationReport($relation);
    }

    /**
     * @param array<string, string> $relation
     */
    private function columnsExist(array $relation): bool
    {
        return Schema::hasTable($relation['child_table'])
            && Schema::hasTable($relation['parent_table'])
            && Schema::hasColumn($relation['child_table'], $relation['child_column'])
            && Schema::hasColumn($relation['parent_table'], $relation['parent_column']);
    }

    /**
     * @param array<string, string> $relation
     * @return array<string, mixed>
     */
    private function skippedRelation(array $relation): array
    {
        return [
            ...$relation,
            'status' => 'skipped',
            'reason' => $this->skipReason($relation),
        ];
    }

    /**
     * @param array<string, string> $relation
     */
    private function skipReason(array $relation): string
    {
        return Schema::hasTable($relation['child_table']) && Schema::hasTable($relation['parent_table'])
            ? 'column missing'
            : 'table missing';
    }

    /**
     * @param array<string, string> $relation
     * @return array<string, mixed>
     */
    private function relationReport(array $relation): array
    {
        $query = DB::table($relation['child_table'] . ' as child')
            ->leftJoin($relation['parent_table'] . ' as parent', 'child.' . $relation['child_column'], '=', 'parent.' . $relation['parent_column'])
            ->whereNotNull('child.' . $relation['child_column'])
            ->whereNull('parent.' . $relation['parent_column']);

        $childType = $this->columns->get($relation['child_table'], $relation['child_column']);
        $parentType = $this->columns->get($relation['parent_table'], $relation['parent_column']);

        return [
            ...$relation,
            'status' => 'ok',
            'orphan_count' => (clone $query)->count(),
            'sample_values' => (clone $query)
                ->select('child.' . $relation['child_column'] . ' as value')
                ->distinct()
                ->limit(5)
                ->pluck('value')
                ->all(),
            'child_type' => $childType,
            'parent_type' => $parentType,
            'type_match' => $this->columns->matches($childType, $parentType),
        ];
    }
}
