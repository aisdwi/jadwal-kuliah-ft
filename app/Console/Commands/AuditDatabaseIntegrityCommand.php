<?php

namespace App\Console\Commands;

use App\Console\Commands\DatabaseIntegrity\DatabaseIntegrityAuditor;
use App\Console\Commands\DatabaseIntegrity\DatabaseIntegrityRenderer;
use Illuminate\Console\Command;

class AuditDatabaseIntegrityCommand extends Command
{
    protected $signature = 'db:audit-integrity {--json : Output machine-readable JSON}';

    protected $description = 'Audit legacy scheduling tables before enabling foreign keys';

    public function handle(): int
    {
        $report = (new DatabaseIntegrityAuditor())->audit();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        (new DatabaseIntegrityRenderer($this))->render($report);

        return self::SUCCESS;
    }
}
