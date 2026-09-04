<?php

namespace App\Modules\Iam\Presentation\Http\Resources;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;

final class UserResource
{
    public static function toArray($user): array
    {
        return [
            'id'               => $user->id,
            'nama_user'        => $user->nama_user,
            'email'            => $user->email,
            'role_id'          => $user->role_id,
            'jurusan_id'       => $user->jurusan_id,
            'jurusan_name'     => $user->jurusan?->nama_jurusan ?? $user->programStudi?->jurusan?->nama_jurusan ?? null,
            'program_studi_id' => $user->program_studi_id,
            'program_studi_name' => $user->programStudi?->nama_prodi,
            'dosen_id'         => $user->dosen_id,
            'role'             => $user->role ? ['id' => $user->role->id, 'role' => RoleName::normalize($user->role->role)] : null,
            'jurusan'          => $user->jurusan ? ['id' => $user->jurusan->id, 'nama_jurusan' => $user->jurusan->nama_jurusan] : null,
            'program_studi'    => $user->programStudi ? ['id' => $user->programStudi->id, 'nama_prodi' => $user->programStudi->nama_prodi] : null,
            'dosen'            => $user->dosen ? ['id' => $user->dosen->id, 'nama_lengkap' => $user->dosen->nama_lengkap] : null,
        ];
    }

    public static function listToArray(array $items): array
    {
        return array_map(fn ($u) => self::toArray($u), $items);
    }

    public static function pagedToArray($result): array
    {
        return PagedResponseFormatter::format($result, [self::class, 'toArray']);
    }
}
