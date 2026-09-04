<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImportMaintainabilityStyleTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function importClasses(): array
    {
        return [
            'kelas' => [__DIR__ . '/../../../app/Modules/KelasKuliah/Infrastructure/Import/KelasImport.php'],
            'kelas kuliah' => [__DIR__ . '/../../../app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImport.php'],
            'dosen' => [__DIR__ . '/../../../app/Modules/MasterAkademik/Infrastructure/Import/DosenImport.php'],
            'mata kuliah' => [__DIR__ . '/../../../app/Modules/MasterAkademik/Infrastructure/Import/MatakuliahImport.php'],
        ];
    }

    #[DataProvider('importClasses')]
    public function test_import_classes_throw_dedicated_exceptions(string $path): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString('use Exception;', $contents);
        $this->assertDoesNotMatchRegularExpression('/throw\s+new\s+\\\\?Exception\s*\(/', $contents);
    }

    #[DataProvider('importClasses')]
    public function test_import_classes_wrap_continue_statements_in_braces(string $path): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertDoesNotMatchRegularExpression('/if\s*\([^)]*\)\s+continue\s*;/', $contents);
    }

    public function test_kelas_kuliah_import_delegates_row_normalization(): void
    {
        $path = __DIR__ . '/../../../app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImport.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('KelasKuliahImportRowFactory::fromArray', $contents);
        $this->assertStringNotContainsString('preg_match', $contents);
        $this->assertStringNotContainsString('preg_replace', $contents);
    }
}
