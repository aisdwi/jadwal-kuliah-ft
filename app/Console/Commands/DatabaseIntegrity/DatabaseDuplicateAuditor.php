<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DatabaseDuplicateAuditor
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function auditAll(): array
    {
        return array_map(
            fn (array $check): array => $this->audit($check),
            DatabaseIntegrityRules::duplicateChecks()
        );
    }

    /**
     * @param array{table: string, columns: array<int, string>, label: string} $check
     * @return array<string, mixed>
     */
    private function audit(array $check): array
    {
        $missingColumn = $this->missingColumn($check);

        if (! Schema::hasTable($check['table']) || $missingColumn !== null) {
            return [
                'label' => $check['label'],
                'status' => 'skipped',
                'reason' => $missingColumn === null ? 'table missing' : 'column missing: ' . $missingColumn,
            ];
        }

        return $this->duplicateReport($check);
    }

    /**
     * @param array{table: string, columns: array<int, string>, label: string} $check
     */
    private function missingColumn(array $check): ?string
    {
        if (! Schema::hasTable($check['table'])) {
            return null;
        }

        return collect($check['columns'])->first(
            fn (string $column): bool => ! Schema::hasColumn($check['table'], $column)
        );
    }

    /**
     * @param array{table: string, columns: array<int, string>, label: string} $check
     * @return array<string, mixed>
     */
    private function duplicateReport(array $check): array
    {
        $query = DB::table($check['table'])
            ->select(array_merge($check['columns'], [DB::raw('COUNT(*) as duplicate_count')]))
            ->groupBy($check['columns'])
            ->having('duplicate_count', '>', 1);

        return [
            'label' => $check['label'],
            'status' => 'ok',
            'duplicate_count' => (clone $query)->count(),
            'sample_rows' => (clone $query)->limit(5)->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }
}
