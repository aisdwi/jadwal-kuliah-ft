<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Console\Command;

final class DatabaseIntegrityRenderer
{
    private readonly DatabaseIssueRenderer $issues;

    private readonly RoleCatalogRenderer $roles;

    public function __construct(private readonly Command $command)
    {
        $this->issues = new DatabaseIssueRenderer($command);
        $this->roles = new RoleCatalogRenderer($command);
    }

    /**
     * @param array<string, mixed> $report
     */
    public function render(array $report): void
    {
        $this->command->info('Database: ' . ($report['database'] ?: '(unknown)'));
        $this->issues->renderOrphans($report['orphan_relations']);
        $this->issues->renderTypeMismatches($report['type_mismatches']);
        $this->issues->renderDuplicates($report['duplicates']);
        $this->roles->render($report['role_catalog']);
        $this->issues->renderWarnings($report['warnings']);
        $this->renderCleanResult($report);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderCleanResult(array $report): void
    {
        if (! $this->hasBlockingIssues($report)) {
            $this->command->info('No blocking integrity issues found for the audited relations.');
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function hasBlockingIssues(array $report): bool
    {
        return $report['orphan_relations'] !== []
            || $report['type_mismatches'] !== []
            || $report['duplicates'] !== []
            || ($report['role_catalog']['missing_role_ids'] ?? []) !== []
            || ($report['role_catalog']['unexpected_role_names'] ?? []) !== [];
    }
}
