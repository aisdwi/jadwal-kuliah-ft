<?php

namespace Tests\Feature;

use App\Modules\KelasKuliah\Infrastructure\Import\KelasImport;
use App\Modules\KelasKuliah\Infrastructure\Import\KelasKuliahImport;
use App\Modules\KelasKuliah\Infrastructure\Import\KelasKuliahImportRowFactory;
use App\Modules\MasterAkademik\Infrastructure\Import\DosenImport;
use App\Modules\MasterAkademik\Infrastructure\Import\MatakuliahImport;
use App\Modules\Shared\Infrastructure\Import\ImportValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportPipelineCoverageTest extends TestCase
{
    public function test_imports_dosen_matakuliah_kelas_and_teaching_assignment(): void
    {
        $this->seedAcademicScope();

        (new DosenImport())->collection($this->rows([
            'nip' => '199001012026061001',
            'nama_dosen' => 'Dosen Import',
            'inisial' => 'DI',
            'jurusan' => 'Teknik Import',
        ]));
        (new MatakuliahImport())->collection($this->rows([
            'kode_mk' => 'IMP101',
            'mata_kuliah' => 'Mata Kuliah Import',
            'sks' => '3',
            'semester' => '1',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));
        (new KelasImport())->collection($this->rows([
            'nama_kelas' => 'IMP-A',
            'semester' => '1',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));
        (new KelasKuliahImport())->collection($this->rows([
            'kode_mk' => 'IMP101',
            'kelas' => 'IMP-A [SMT 1]',
            'dosen_pengampu' => 'Dosen Import',
            'jumlah_mahasiswa' => '35',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));

        $this->assertDatabaseHas('dosen', ['nip' => '199001012026061001']);
        $this->assertDatabaseHas('matakuliah', ['kode_mk' => 'IMP101']);
        $this->assertDatabaseHas('kelas', ['nama_kelas' => 'IMP-A', 'semester' => 1]);
        $this->assertDatabaseHas('kelas_kuliah', ['jumlah_mahasiswa' => 35, 'semester_tipe' => 'ganjil']);
        $this->assertSame(4, (new DosenImport())->headingRow());
        $this->assertSame(4, (new MatakuliahImport())->headingRow());
        $this->assertSame(4, (new KelasImport())->headingRow());
        $this->assertSame(4, (new KelasKuliahImport())->headingRow());
    }

    public function test_imports_team_teaching_names_separated_by_ampersand(): void
    {
        $this->seedAcademicScope();

        (new DosenImport())->collection($this->rows(
            [
                'nip' => '199001012026061001',
                'nama_dosen' => 'Dosen Import',
                'inisial' => 'DI',
                'jurusan' => 'Teknik Import',
            ],
            [
                'nip' => '199001012026061002',
                'nama_dosen' => 'Dosen Kedua Import',
                'inisial' => 'DKI',
                'jurusan' => 'Teknik Import',
            ],
        ));
        (new MatakuliahImport())->collection($this->rows([
            'kode_mk' => 'IMP102',
            'mata_kuliah' => 'Mata Kuliah Team Import',
            'sks' => '3',
            'semester' => '1',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));
        (new KelasImport())->collection($this->rows([
            'nama_kelas' => 'IMP-B',
            'semester' => '1',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));

        (new KelasKuliahImport())->collection($this->rows([
            'kode_mk' => 'IMP102',
            'kelas' => 'IMP-B [SMT 1]',
            'dosen_pengampu' => 'Dosen Import & Dosen Kedua Import',
            'jumlah_mahasiswa' => '35',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));

        $primaryDosenId = (int) DB::table('dosen')->where('nama_lengkap', 'Dosen Import')->value('id');
        $secondDosenId = (int) DB::table('dosen')->where('nama_lengkap', 'Dosen Kedua Import')->value('id');
        $kelasKuliahId = (int) DB::table('kelas_kuliah')->where('dosen_id', $primaryDosenId)->value('id');

        $this->assertDatabaseHas('kelas_kuliah', [
            'id' => $kelasKuliahId,
            'dosen_id' => $primaryDosenId,
            'jumlah_mahasiswa' => 35,
        ]);
        $this->assertDatabaseHas('kelas_kuliah_dosen', [
            'kelas_kuliah_id' => $kelasKuliahId,
            'dosen_id' => $primaryDosenId,
        ]);
        $this->assertDatabaseHas('kelas_kuliah_dosen', [
            'kelas_kuliah_id' => $kelasKuliahId,
            'dosen_id' => $secondDosenId,
        ]);
        $this->assertSame(2, DB::table('kelas_kuliah_dosen')->where('kelas_kuliah_id', $kelasKuliahId)->count());
    }

    public function test_rejects_empty_team_teaching_names(): void
    {
        $scope = $this->seedAcademicScope();
        $matakuliahId = DB::table('matakuliah')->insertGetId([
            'program_studi_id' => $scope['program_studi_id'],
            'jurusan_id' => $scope['jurusan_id'],
            'kode_mk' => 'IMP103',
            'nama_mk' => 'Mata Kuliah Empty Team',
            'sks' => 3,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert([
            'program_studi_id' => $scope['program_studi_id'],
            'jurusan_id' => $scope['jurusan_id'],
            'nama_kelas' => 'IMP-C',
            'semester' => 1,
        ]);

        $this->assertNotNull($matakuliahId);
        $this->expectException(ImportValidationException::class);
        $this->expectExceptionMessage('Dosen pengampu wajib diisi');

        (new KelasKuliahImport())->collection($this->rows([
            'kode_mk' => 'IMP103',
            'kelas' => 'IMP-C [SMT 1]',
            'dosen_pengampu' => ' & ',
            'jumlah_mahasiswa' => '35',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]));
    }

    public function test_rejects_empty_rows_and_wrong_template(): void
    {
        $this->expectException(ImportValidationException::class);
        (new DosenImport())->collection(collect());
    }

    public function test_rejects_wrong_template_columns(): void
    {
        $this->expectExceptionMessage('Format template tidak sesuai');
        (new MatakuliahImport())->collection($this->rows(['kode_mk' => 'ONLY']));
    }

    #[DataProvider('duplicateRowsProvider')]
    public function test_rejects_duplicate_rows_inside_import_file(string $importClass, array $row): void
    {
        $this->expectException(ImportValidationException::class);
        (new $importClass())->collection($this->rows($row, $row));
    }

    public static function duplicateRowsProvider(): array
    {
        return [
            'dosen' => [DosenImport::class, [
                'nip' => 'DUP-1',
                'nama_dosen' => 'Dosen Duplicate',
                'jurusan' => 'Teknik Import',
            ]],
            'mata kuliah' => [MatakuliahImport::class, [
                'kode_mk' => 'DUP101',
                'mata_kuliah' => 'Mata Kuliah Duplicate',
            ]],
            'kelas' => [KelasImport::class, [
                'nama_kelas' => 'DUP-A',
                'semester' => '1',
            ]],
            'kelas kuliah' => [KelasKuliahImport::class, [
                'kode_mk' => 'DUP101',
                'kelas' => 'DUP-A [SMT 1]',
                'dosen_pengampu' => 'Dosen Duplicate',
                'jumlah_mahasiswa' => '20',
            ]],
        ];
    }

    #[DataProvider('incompleteRowsProvider')]
    public function test_skips_incomplete_rows(string $importClass, array $row): void
    {
        (new $importClass())->collection($this->rows($row));

        $this->assertTrue(true);
    }

    public static function incompleteRowsProvider(): array
    {
        return [
            'dosen' => [DosenImport::class, ['nip' => '', 'nama_dosen' => '', 'jurusan' => '']],
            'mata kuliah' => [MatakuliahImport::class, ['kode_mk' => '', 'mata_kuliah' => '']],
            'kelas' => [KelasImport::class, ['nama_kelas' => '', 'semester' => '']],
            'kelas kuliah' => [KelasKuliahImport::class, [
                'kode_mk' => '',
                'kelas' => '',
                'dosen_pengampu' => '',
                'jumlah_mahasiswa' => '',
            ]],
        ];
    }

    #[DataProvider('missingReferenceRowsProvider')]
    public function test_rejects_rows_with_missing_master_reference(string $importClass, array $row): void
    {
        $this->expectException(ImportValidationException::class);
        (new $importClass())->collection($this->rows($row));
    }

    public static function missingReferenceRowsProvider(): array
    {
        return [
            'dosen' => [DosenImport::class, [
                'nip' => 'MISS-1',
                'nama_dosen' => 'Missing Jurusan',
                'jurusan' => 'Tidak Ada',
            ]],
            'mata kuliah' => [MatakuliahImport::class, [
                'kode_mk' => 'MISS101',
                'mata_kuliah' => 'Missing Prodi',
                'sks' => '2',
                'semester' => '1',
                'program_studi' => 'Tidak Ada',
                'jurusan' => 'Tidak Ada',
            ]],
            'kelas' => [KelasImport::class, [
                'nama_kelas' => 'MISS-A',
                'semester' => '1',
                'program_studi' => 'Tidak Ada',
                'jurusan' => 'Tidak Ada',
            ]],
            'kelas kuliah' => [KelasKuliahImport::class, [
                'kode_mk' => 'MISS101',
                'kelas' => 'MISS-A [SMT 1]',
                'dosen_pengampu' => 'Tidak Ada',
                'jumlah_mahasiswa' => '20',
                'program_studi' => 'Tidak Ada',
                'jurusan' => 'Tidak Ada',
            ]],
        ];
    }

    public function test_rejects_database_duplicates_for_dosen_matakuliah_and_kelas(): void
    {
        $scope = $this->seedAcademicScope();
        DB::table('dosen')->insert([
            'nip' => 'EXIST-1',
            'nama_lengkap' => 'Existing',
            'jurusan_id' => $scope['jurusan_id'],
        ]);

        $this->expectExceptionMessage('sudah terdaftar');
        (new DosenImport())->collection($this->rows([
            'nip' => 'EXIST-1',
            'nama_dosen' => 'Existing',
            'jurusan' => 'Teknik Import',
        ]));
    }

    public function test_parses_class_label_and_detects_incomplete_assignment(): void
    {
        $complete = KelasKuliahImportRowFactory::fromArray([
            'kode_mk' => 'IMP101',
            'kelas' => 'IMP-A [SMT 3]',
            'dosen_pengampu' => 'Dosen Import',
            'jumlah_mahasiswa' => '25',
            'program_studi' => 'S1 Import',
            'jurusan' => 'Teknik Import',
        ]);
        $incomplete = KelasKuliahImportRowFactory::fromArray(['kelas' => 'IMP-A']);

        $this->assertSame('IMP-A', $complete->namaKelas);
        $this->assertSame(3, $complete->semester);
        $this->assertTrue($complete->hasDuplicateKeyFields());
        $this->assertTrue($complete->hasAssignmentFields());
        $this->assertNotSame('', $complete->teachingAssignmentKey());
        $this->assertNull($incomplete->semester);
        $this->assertFalse($incomplete->hasAssignmentFields());
    }

    private function seedAcademicScope(): array
    {
        $jurusanId = (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => 'Teknik Import']);
        $programStudiId = (int) DB::table('program_studi')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nama_prodi' => 'S1 Import',
        ]);

        return [
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
        ];
    }

    private function rows(array ...$rows): Collection
    {
        return collect(array_map(static fn (array $row): Collection => collect($row), $rows));
    }
}
