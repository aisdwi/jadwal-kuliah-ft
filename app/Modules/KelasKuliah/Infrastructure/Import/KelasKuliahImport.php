<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\MataKuliahModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use App\Modules\Shared\Infrastructure\Import\ImportValidationException;
use App\Modules\Shared\Infrastructure\Import\ValidatesImportRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KelasKuliahImport implements ToCollection, WithHeadingRow
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
            ['kode_mk', 'dosen_pengampu', 'jumlah_mahasiswa'],
            'Format template tidak sesuai. Harap gunakan format template Kelas Kuliah yang disediakan.',
        );
        $this->ensureNoDuplicateTeachingAssignments($rows);
        $this->importTeachingAssignments($rows);
    }

    private function ensureNoDuplicateTeachingAssignments(Collection $rows): void
    {
        $combos = [];
        foreach ($rows as $index => $row) {
            $importRow = KelasKuliahImportRowFactory::fromArray($row->toArray());

            if (!$importRow->hasDuplicateKeyFields()) {
                continue;
            }

            $key = $importRow->teachingAssignmentKey();
            if (in_array($key, $combos, true)) {
                $rowNum = $index + 5;
                throw new ImportValidationException("Terdapat plot kelas, matakuliah, dan dosen yang persis sama di dalam file Excel pada baris $rowNum.");
            }
            $combos[] = $key;
        }
    }

    private function importTeachingAssignments(Collection $rows): void
    {
        foreach ($rows as $row) {
            $importRow = KelasKuliahImportRowFactory::fromArray($row->toArray());

            if (!$importRow->hasAssignmentFields()) {
                continue;
            }

            $jurusan    = JurusanModel::where('nama_jurusan', $importRow->jurusan)->first();
            $prodi      = ProgramStudiModel::where('nama_prodi', $importRow->programStudi)->first();
            $matakuliah = MataKuliahModel::where('kode_mk', $importRow->kodeMk)->first();
            $kelas      = KelasModel::where('nama_kelas', $importRow->namaKelas)->where('semester', $importRow->semester)->first();

            if (!$jurusan || !$prodi || !$matakuliah || !$kelas) {
                throw new ImportValidationException("Data Master (Jurusan/Prodi/Matakuliah/Kelas) tidak ditemukan untuk baris: MK {$importRow->kodeMk}, Kelas {$importRow->kelasRaw}");
            }

            $dosenTeam = $this->resolveTeachingTeam($importRow->pengampu);
            $dosen = $dosenTeam[0];

            if (KelasKuliahModel::where('kelas_id', $kelas->id)->where('matakuliah_id', $matakuliah->id)->where('dosen_id', $dosen->id)->exists()) {
                throw new ImportValidationException("Jadwal dosen '{$importRow->pengampu}' mengampu '{$importRow->kodeMk}' di kelas '{$importRow->kelasRaw}' sudah ada di database.");
            }

            $kelasKuliah = KelasKuliahModel::create([
                'kelas_id'         => $kelas->id,
                'matakuliah_id'    => $matakuliah->id,
                'dosen_id'         => $dosen->id,
                'jumlah_mahasiswa' => $importRow->jumlahMahasiswa,
            ]);

            $this->syncTeachingTeam($kelasKuliah, $dosenTeam);
        }
    }

    /**
     * @return list<DosenModel>
     */
    private function resolveTeachingTeam(string $pengampu): array
    {
        $dosens = [];

        foreach ($this->teachingTeamNames($pengampu) as $name) {
            $dosen = DosenModel::where('nama_lengkap', $name)->first();
            if (!$dosen) {
                throw new ImportValidationException("Dosen dengan nama '{$name}' tidak ditemukan di database.");
            }

            $dosens[] = $dosen;
        }

        return $dosens;
    }

    /**
     * @return list<string>
     */
    private function teachingTeamNames(string $pengampu): array
    {
        $names = array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            preg_split('/\s*&\s*/', $pengampu) ?: [],
        )));

        if ($names === []) {
            throw new ImportValidationException('Dosen pengampu wajib diisi.');
        }

        if (count($names) !== count(array_unique($names))) {
            throw new ImportValidationException('Dosen pengampu tidak boleh duplikat. Setiap dosen hanya boleh ditulis satu kali.');
        }

        return $names;
    }

    /**
     * @param list<DosenModel> $dosenTeam
     */
    private function syncTeachingTeam(KelasKuliahModel $kelasKuliah, array $dosenTeam): void
    {
        $syncPayload = [];

        foreach ($dosenTeam as $dosen) {
            $syncPayload[(int) $dosen->id] = [
                'preferred_slot_id' => null,
                'is_external' => false,
            ];
        }

        $kelasKuliah->dosens()->sync($syncPayload);
    }
}
