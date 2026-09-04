<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Support;

use Illuminate\Http\Request;

final readonly class KelasKuliahIndexFilters
{
    private function __construct(
        public ?int $programStudiId,
        public ?int $jurusanId,
        public ?int $semester,
        public mixed $semesterTipe,
        public ?int $dosenId,
        public mixed $search,
        public mixed $perPage,
        public ?bool $isScheduled,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            programStudiId: $request->integer('program_studi_id') ?: null,
            jurusanId: $request->integer('jurusan_id') ?: null,
            semester: $request->integer('semester') ?: null,
            semesterTipe: $request->input('semester_tipe'),
            dosenId: $request->integer('dosen_id') ?: null,
            search: $request->input('search'),
            perPage: $request->input('per_page', 'all'),
            isScheduled: KelasKuliahScheduledFilter::fromRequest($request),
        );
    }

    public function toArray(): array
    {
        return [
            'program_studi_id' => $this->programStudiId,
            'jurusan_id' => $this->jurusanId,
            'semester' => $this->semester,
            'semester_tipe' => $this->semesterTipe,
            'dosen_id' => $this->dosenId,
            'search' => $this->search,
            'per_page' => $this->perPage,
            'is_scheduled' => $this->isScheduled,
        ];
    }

}
