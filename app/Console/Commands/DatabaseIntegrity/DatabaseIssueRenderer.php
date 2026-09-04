<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Console\Command;

final class DatabaseIssueRenderer
{
    public function __construct(private readonly Command $command)
    {
    }

    public function renderOrphans(array $rows): void
    {
        if ($rows === []) {
            $this->command->info('No orphan rows found on audited FK candidates.');
            return;
        }

        $this->command->warn('Orphan rows found:');
        $this->command->table(['Child', 'Column', 'Parent', 'Orphans', 'Sample'], $this->orphanRows($rows));
    }

    public function renderTypeMismatches(array $rows): void
    {
        if ($rows === []) {
            $this->command->info('No column type mismatches found on audited FK candidates.');
            return;
        }

        $this->command->warn('Column type mismatches found:');
        $this->command->table(['Child', 'Child Type', 'Parent', 'Parent Type'], $this->typeMismatchRows($rows));
    }

    public function renderDuplicates(array $rows): void
    {
        if ($rows === []) {
            $this->command->info('No duplicate rows found on audited unique candidates.');
            return;
        }

        $this->command->warn('Duplicate rows found:');
        $this->command->table(['Constraint Candidate', 'Duplicate Groups', 'Sample'], $this->duplicateRows($rows));
    }

    public function renderWarnings(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->command->warn('Skipped checks:');
        $this->command->table(['Scope', 'Reason'], $this->warningRows($rows));
    }

    private function orphanRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            $row['child_table'],
            $row['child_column'],
            $row['parent_table'],
            (string) $row['orphan_count'],
            implode(', ', array_map('strval', $row['sample_values'])),
        ], $rows);
    }

    private function typeMismatchRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            $row['child_table'] . '.' . $row['child_column'],
            $row['child_type'] ?? '(unknown)',
            $row['parent_table'] . '.' . $row['parent_column'],
            $row['parent_type'] ?? '(unknown)',
        ], $rows);
    }

    private function duplicateRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            $row['label'],
            (string) $row['duplicate_count'],
            json_encode($row['sample_rows'], JSON_UNESCAPED_UNICODE),
        ], $rows);
    }

    private function warningRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            ($row['child_table'] ?? $row['label'] ?? 'check'),
            $row['reason'] ?? 'unknown',
        ], $rows);
    }
}
