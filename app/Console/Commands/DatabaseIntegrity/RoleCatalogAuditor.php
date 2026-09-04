<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RoleCatalogAuditor
{
    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        if (! Schema::hasTable('role')) {
            return [
                'status' => 'skipped',
                'reason' => 'role table missing',
            ];
        }

        $roles = $this->rolesById();

        return [
            'status' => 'ok',
            'roles' => $roles,
            'missing_role_ids' => $this->missingRoleIds($roles),
            'unexpected_role_names' => $this->unexpectedRoleNames($roles),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function rolesById(): array
    {
        return DB::table('role')
            ->select('id', 'role')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => (string) $row->role])
            ->all();
    }

    /**
     * @param array<int, string> $roles
     * @return array<int, int>
     */
    private function missingRoleIds(array $roles): array
    {
        return array_values(array_filter(
            array_keys(DatabaseIntegrityRules::expectedRoles()),
            fn (int $id): bool => ! array_key_exists($id, $roles)
        ));
    }

    /**
     * @param array<int, string> $roles
     * @return array<int, array{id: int, expected: string, actual: string}>
     */
    private function unexpectedRoleNames(array $roles): array
    {
        $unexpected = [];

        foreach (DatabaseIntegrityRules::expectedRoles() as $id => $name) {
            if (array_key_exists($id, $roles) && $roles[$id] !== $name) {
                $unexpected[] = ['id' => $id, 'expected' => $name, 'actual' => $roles[$id]];
            }
        }

        return $unexpected;
    }
}
