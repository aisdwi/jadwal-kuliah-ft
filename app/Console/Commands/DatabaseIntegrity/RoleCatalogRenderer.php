<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Console\Command;

final class RoleCatalogRenderer
{
    public function __construct(private readonly Command $command)
    {
    }

    /**
     * @param array<string, mixed> $catalog
     */
    public function render(array $catalog): void
    {
        if (($catalog['status'] ?? null) === 'skipped') {
            $this->command->warn('Role catalog audit skipped: ' . $catalog['reason']);
            return;
        }

        if ($this->isAligned($catalog)) {
            $this->command->info('Role catalog is aligned with the current application policy.');
            return;
        }

        $this->renderMissingRoles($catalog['missing_role_ids'] ?? []);
        $this->renderUnexpectedRoles($catalog['unexpected_role_names'] ?? []);
    }

    /**
     * @param array<string, mixed> $catalog
     */
    private function isAligned(array $catalog): bool
    {
        return ($catalog['missing_role_ids'] ?? []) === []
            && ($catalog['unexpected_role_names'] ?? []) === [];
    }

    private function renderMissingRoles(array $roleIds): void
    {
        if ($roleIds !== []) {
            $this->command->warn('Missing role IDs: ' . implode(', ', $roleIds));
        }
    }

    private function renderUnexpectedRoles(array $roles): void
    {
        if ($roles === []) {
            return;
        }

        $this->command->warn('Unexpected role names:');
        $this->command->table(['ID', 'Expected', 'Actual'], $this->unexpectedRoleRows($roles));
    }

    private function unexpectedRoleRows(array $roles): array
    {
        return array_map(fn (array $row): array => [
            (string) $row['id'],
            $row['expected'],
            $row['actual'],
        ], $roles);
    }
}
