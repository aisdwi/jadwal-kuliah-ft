<?php

namespace App\Modules\Shared\Application\Service;

final class AcademicScopePayload
{
    public static function make(
        string $roleName,
        ?int $jurusanId,
        ?int $programStudiId,
        bool $defaultsToOwnJurusan,
        bool $defaultsToOwnProgramStudi,
        bool $isJurusanRole,
    ): array {
        $isJurusanRestricted = $isJurusanRole && !empty($jurusanId);
        $isProgramStudiRestricted = $defaultsToOwnProgramStudi && !empty($programStudiId);

        return [
            'role_name' => $roleName,
            'jurusan_id' => $jurusanId,
            'default_jurusan_id' => $defaultsToOwnJurusan ? $jurusanId : null,
            'default_program_studi_id' => $defaultsToOwnProgramStudi ? $programStudiId : null,
            'default_by_jurusan' => $defaultsToOwnJurusan && !empty($jurusanId),
            'program_studi_id' => $programStudiId,
            'restrict_by_jurusan' => $isJurusanRestricted,
            'restrict_by_program_studi' => $isProgramStudiRestricted,
            'is_restricted' => $isJurusanRestricted || $isProgramStudiRestricted,
            'label' => self::label($isProgramStudiRestricted, $isJurusanRestricted),
        ];
    }

    private static function label(bool $isProgramStudiRestricted, bool $isJurusanRestricted): string
    {
        if ($isProgramStudiRestricted) {
            return 'Program Studi';
        }

        return $isJurusanRestricted ? 'Jurusan' : 'Semua Data';
    }
}
