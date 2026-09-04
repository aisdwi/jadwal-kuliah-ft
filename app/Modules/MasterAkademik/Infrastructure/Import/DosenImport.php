<?php

namespace App\Modules\MasterAkademik\Infrastructure\Import;

use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\Shared\Infrastructure\Import\ImportValidationException;
use App\Modules\Shared\Infrastructure\Import\ValidatesImportRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DosenImport implements ToCollection, WithHeadingRow
{
    use ValidatesImportRows;

    public function headingRow(): int
    {
        return 4;
    }

    public function collection(Collection $rows): void
    {
        $this->ensureRowsNotEmpty($rows);
        $this->ensureRequiredColumns(
            $rows->first()->toArray(),
            ['nip', 'nama_dosen'],
            'Format template tidak sesuai. Harap gunakan format template Dosen yang disediakan.',
        );
        $nipList = $this->collectUniqueNips($rows);
        $this->ensureNipsAreNotRegistered($nipList);
        $this->importDosen($rows);
    }

    /**
     * @return array<int, string>
     */
    private function collectUniqueNips(Collection $rows): array
    {
        $nipList = [];
        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $nip = trim($data['nip'] ?? '');

            if (empty($nip)) {
                continue;
            }

            if (in_array($nip, $nipList, true)) {
                $rowNum = $index + 5;
                throw new ImportValidationException("Terdapat NIP ganda di dalam file Excel pada baris $rowNum: $nip");
            }
            $nipList[] = $nip;
        }

        return $nipList;
    }

    /**
     * @param array<int, string> $nipList
     */
    private function ensureNipsAreNotRegistered(array $nipList): void
    {
        if (!empty($nipList)) {
            $existing = DosenModel::whereIn('nip', $nipList)->pluck('nip')->toArray();
            if (!empty($existing)) {
                throw new ImportValidationException('Dosen dengan NIP berikut sudah terdaftar di database: ' . implode(', ', $existing));
            }
        }
    }

    private function importDosen(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row->toArray();
            $nip          = trim($data['nip'] ?? '');
            $nama         = trim($data['nama_dosen'] ?? '');
            $inisial      = trim($data['inisial'] ?? '');
            $nama_jurusan = trim($data['jurusan'] ?? '');

            if (empty($nip) || empty($nama) || empty($nama_jurusan)) {
                continue;
            }

            $jurusan = JurusanModel::where('nama_jurusan', $nama_jurusan)->first();
            if (!$jurusan) {
                throw new ImportValidationException("Jurusan '$nama_jurusan' tidak ditemukan di master data jurusan.");
            }

            DosenModel::create([
                'nip'          => $nip,
                'nama_lengkap' => $nama,
                'inisial'      => $inisial,
                'jurusan_id'   => $jurusan->id,
            ]);
        }
    }
}
