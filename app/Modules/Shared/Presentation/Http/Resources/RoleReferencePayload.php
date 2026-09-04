<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

use App\Modules\Iam\Application\Support\RoleName;

final class RoleReferencePayload
{
    public static function from(mixed $roleRecord): array
    {
        $role = RoleName::normalize($roleRecord['role'] ?? $roleRecord->role ?? '');

        return [
            'id' => $roleRecord['id'] ?? $roleRecord->id,
            'role' => $role,
            'name' => $role,
            'display_name' => $role,
        ];
    }
}
