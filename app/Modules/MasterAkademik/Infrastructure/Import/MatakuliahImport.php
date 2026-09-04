<?php

namespace App\Modules\MasterAkademik\Infrastructure\Import;

use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\MataKuliahModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use App\Modules\Shared\Infrastructure\Import\ImportValidationException;
use App\Modules\Shared\Infrastructure\Import\ChecksImportRowCompleteness;
use App\Modules\Shared\Infrastructure\Import\ReadsImportRowValues;
use App\Modules\Shared\Infrastructure\Import\ValidatesImportRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MatakuliahImport implements ToCollection, WithHeadingRow
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
            ['kode_mk', 'mata_kuliah'],
            'Format template tidak sesuai. Harap gunakan format template Mata Kuliah yang disediakan.',
        );
        $kodeMkList = $this->collectUniqueKodeMk($rows);
        $this->ensureKodeMkAreNotRegistered($kodeMkList);
        $this->importMataKuliah($rows);
    }

    /**
     * @return array<int, string>
     */
    private function collectUniqueKodeMk(Collection $rows): array
    {
        $kodeMkList = [];
        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $kode = $this->stringValue($data, 'kode_mk');

            if ($kode === '') {
                continue;
            }

            if (in_array($kode, $kodeMkList, true)) {
                $rowNum = $index + 5;
                throw new ImportValidationException("Terdapat Kode MK ganda di dalam file Excel pada baris $rowNum: $kode");
            }
            $kodeMkList[] = $kode;
        }

        return $kodeMkList;
    }

    /**
     * @param array<int, string> $kodeMkList
     */
    private function ensureKodeMkAreNotRegistered(array $kodeMkList): void
    {
        if (!empty($kodeMkList)) {
            $existing = MataKuliahModel::whereIn('kode_mk', $kodeMkList)->pluck('kode_mk')->toArray();
            if (!empty($existing)) {
                throw new ImportValidationException('Beberapa Mata Kuliah sudah ada di database: ' . implode(', ', $existing));
            }
        }
    }

    private function importMataKuliah(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row->toArray();
            $kode = $this->stringValue($data, 'kode_mk');
            $nama = $this->stringValue($data, 'mata_kuliah');
            $sks = $this->stringValue($data, 'sks');
            $semester = $this->stringValue($data, 'semester');
            $nama_prodi = $this->stringValue($data, 'program_studi');
            $nama_jurusan = $this->stringValue($data, 'jurusan');

            if ($this->hasEmptyValue([$kode, $nama, $sks, $semester, $nama_prodi, $nama_jurusan])) {
                continue;
            }

            $jurusan = JurusanModel::where('nama_jurusan', $nama_jurusan)->first();
            $prodi   = ProgramStudiModel::where('nama_prodi', $nama_prodi)
                ->where('jurusan_id', $jurusan->id ?? null)
                ->first();

            if (!$jurusan || !$prodi) {
                throw new ImportValidationException("Jurusan atau Program Studi tidak ditemukan untuk Mata Kuliah: $nama");
            }

            MataKuliahModel::create([
                'kode_mk'          => $kode,
                'nama_mk'          => $nama,
                'sks'              => (int) $sks,
                'semester'         => (int) $semester,
                'jurusan_id'       => $jurusan->id,
                'program_studi_id' => $prodi->id,
            ]);
        }
    }
}
