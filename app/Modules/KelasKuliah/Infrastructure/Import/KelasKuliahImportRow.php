<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

use App\Modules\KelasKuliah\Domain\Entities\TeachingAssignmentLabelKey;

final class KelasKuliahImportRow
{
    public function __construct(
        public readonly string $kodeMk,
        public readonly string $kelasRaw,
        public readonly string $namaKelas,
        public readonly ?int $semester,
        public readonly int $jumlahMahasiswa,
        public readonly string $pengampu,
        public readonly string $programStudi,
        public readonly string $jurusan,
    ) {}

    public function hasDuplicateKeyFields(): bool
    {
        return KelasKuliahImportRowCompleteness::hasValues([$this->kodeMk, $this->kelasRaw, $this->pengampu]);
    }

    public function hasAssignmentFields(): bool
    {
        return KelasKuliahImportRowCompleteness::hasValues([
            $this->kodeMk,
            $this->namaKelas,
            $this->semester,
            $this->jumlahMahasiswa,
            $this->pengampu,
            $this->programStudi,
            $this->jurusan,
        ]);
    }

    public function teachingAssignmentKey(): string
    {
        return TeachingAssignmentLabelKey::from(
            $this->kodeMk,
            $this->kelasRaw,
            $this->pengampu,
        );
    }

}
