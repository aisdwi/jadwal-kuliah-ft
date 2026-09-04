<?php

namespace App\Modules\Laporan\Application\Service;

use App\Modules\Laporan\Application\Port\ExcelExporterPort;
use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;

class LaporanAppService
{
    public function __construct(
        protected JadwalRepository $jadwalRepo,
        protected ProgramStudiRepository $prodiRepo,
        protected ExcelExporterPort $exporter,
    ) {}

    public function exportJadwal(int $programStudiId, ?int $semester = null): array
    {
        $jadwals = $this->jadwalRepo->findByProgramStudi($programStudiId, $semester);

        $prodi = $this->prodiRepo->findById($programStudiId);
        $prodiNama = $prodi?->nama_program_studi ?? $prodi?->namaProdi;

        $semesterLabel = $semester ? "_Semester{$semester}" : '';
        $prodiSlug = $prodiNama
            ? preg_replace('/\W/', '_', $prodiNama)
            : "Prodi{$programStudiId}";
        $fileName = "Jadwal_{$prodiSlug}{$semesterLabel}.xlsx";

        $fileContent = $this->exporter->exportJadwal(
            $jadwals,
            $prodiNama,
            $semester,
        );

        return [
            'fileContent' => $fileContent,
            'fileName' => $fileName,
            'jumlahJadwal' => count($jadwals),
            'programStudiNama' => $prodiNama,
            'semester' => $semester,
        ];
    }
}
