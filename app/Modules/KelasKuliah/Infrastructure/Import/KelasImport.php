<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasModel;
use App\Modules\Shared\Infrastructure\Import\ImportValidationException;
use App\Modules\Shared\Infrastructure\Import\ChecksImportRowCompleteness;
use App\Modules\Shared\Infrastructure\Import\ReadsImportRowValues;
use App\Modules\Shared\Infrastructure\Import\ValidatesImportRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KelasImport implements ToCollection, WithHeadingRow
{
    use ChecksImportRowCompleteness, ReadsImportRowValues, ValidatesImportRows;

    public function headingRow(): int
    {
        return 4;
    }

    public function collection(Collection $rows): void
    {
        $this->ensureRowsNotEmpty($rows);
        $this->ensureRequiredColumns(
            $rows->first()->toArray(),
            ['nama_kelas', 'semester'],
            'Format template tidak sesuai. Harap gunakan format template Kelas yang disediakan.',
        );
        $this->ensureNoDuplicateClasses($rows);
        $this->importClasses($rows);
    }

    private function ensureNoDuplicateClasses(Collection $rows): void
    {
        $combos = [];
        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $namaKelas = $this->stringValue($data, 'nama_kelas');
            $semester = $this->stringValue($data, 'semester');

            if ($this->hasEmptyValue([$namaKelas, $semester])) {
                continue;
            }

            $key = $namaKelas . '-' . $semester;
            if (in_array($key, $combos, true)) {
                $rowNum = $index + 5;
                throw new ImportValidationException("Terdapat Kelas dan Semester yang identik di dalam file Excel pada baris $rowNum: $namaKelas (Semester $semester)");
            }
            $combos[] = $key;
        }
    }

    private function importClasses(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row->toArray();
            $namaKelas = $this->stringValue($data, 'nama_kelas');
            $semester = $this->stringValue($data, 'semester');
            $namaProdi = $this->stringValue($data, 'program_studi');
            $namaJurusan = $this->stringValue($data, 'jurusan');

            if ($this->hasEmptyValue([$namaKelas, $semester, $namaProdi, $namaJurusan])) {
                continue;
            }

            $jurusan = JurusanModel::where('nama_jurusan', $namaJurusan)->first();
            $prodi   = ProgramStudiModel::where('nama_prodi', $namaProdi)
                ->where('jurusan_id', $jurusan->id ?? null)
                ->first();

            if (!$jurusan || !$prodi) {
                throw new ImportValidationException("Jurusan atau Program Studi tidak ditemukan untuk kelas: $namaKelas");
            }

            if (KelasModel::where('nama_kelas', $namaKelas)->where('semester', (int) $semester)->exists()) {
                throw new ImportValidationException("Kelas '$namaKelas' untuk semester '$semester' sudah ada di database.");
            }

            KelasModel::create([
                'nama_kelas'        => $namaKelas,
                'semester'          => (int) $semester,
                'program_studi_id'  => $prodi->id,
                'jurusan_id'        => $jurusan->id,
            ]);
        }
    }
}
