<?php

namespace App\Modules\Shared\Application\Service;

use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;

final class AcademicScopeJurusanResolver
{
    public static function resolve(?object $user, ?int $programStudiId): ?int
    {
        $jurusanId = $user?->jurusan_id ?: $user?->programStudi?->jurusan_id;

        if (!$jurusanId && $programStudiId !== null) {
            $jurusanId = ProgramStudiModel::whereKey($programStudiId)->value('jurusan_id');
        }

        return empty($jurusanId) ? null : (int) $jurusanId;
    }
}
