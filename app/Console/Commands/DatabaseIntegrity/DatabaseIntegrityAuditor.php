<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Support\Facades\DB;

final class DatabaseIntegrityAuditor
{
    public function __construct(
        private readonly DatabaseRelationAuditor $relations = new DatabaseRelationAuditor(),
        private readonly DatabaseDuplicateAuditor $duplicates = new DatabaseDuplicateAuditor(),
        private readonly RoleCatalogAuditor $roles = new RoleCatalogAuditor(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $report = $this->emptyReport();
        $this->appendRelationReports($report);
        $this->appendDuplicateReports($report);
        $report['role_catalog'] = $this->roles->audit();

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(): array
    {
        return [
            'database' => DB::getDatabaseName(),
            'orphan_relations' => [],
            'type_mismatches' => [],
            'duplicates' => [],
            'role_catalog' => [],
            'warnings' => [],
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function appendRelationReports(array &$report): void
    {
        foreach ($this->relations->auditAll() as $relationReport) {
            $this->appendRelationReport($report, $relationReport);
        }
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $relationReport
     */
    private function appendRelationReport(array &$report, array $relationReport): void
    {
        if (($relationReport['status'] ?? null) === 'skipped') {
            $report['warnings'][] = $relationReport;
            return;
        }

        if (($relationReport['orphan_count'] ?? 0) > 0) {
            $report['orphan_relations'][] = $relationReport;
        }

        if (($relationReport['type_match'] ?? true) === false) {
            $report['type_mismatches'][] = $relationReport;
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function appendDuplicateReports(array &$report): void
    {
        foreach ($this->duplicates->auditAll() as $duplicateReport) {
            if (($duplicateReport['duplicate_count'] ?? 0) > 0) {
                $report['duplicates'][] = $duplicateReport;
            }
        }
    }
}
