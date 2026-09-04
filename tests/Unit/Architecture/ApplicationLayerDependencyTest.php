<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ApplicationLayerDependencyTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../';

    public function test_application_layer_infrastructure_references_match_current_baseline(): void
    {
        $this->assertSame(
            [
                'app/Modules/Penjadwalan/Application/Service/JadwalAssignmentValidator.php' => [
                    'App\\Modules\\Resource\\Infrastructure\\',
                ],
                'app/Modules/Shared/Application/Service/AcademicScopeJurusanResolver.php' => [
                    'App\\Modules\\MasterAkademik\\Infrastructure\\',
                ],
                'app/Modules/Shared/Application/Traits/FiltersByJurusan.php' => [
                    'App\\Modules\\MasterAkademik\\Infrastructure\\',
                ],
            ],
            $this->collectForbiddenReferences([
                'app/Modules/KelasKuliah/Application',
                'app/Modules/Penjadwalan/Application',
                'app/Modules/Resource/Application',
                'app/Modules/Shared/Application',
            ], '/App\\\\Modules\\\\.*\\\\Infrastructure\\\\/')
        );
    }

    public function test_kelas_kuliah_controller_does_not_run_database_queries_directly(): void
    {
        $path = realpath(self::ROOT . '/app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php');
        $this->assertIsString($path);

        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString('DB::', $contents);
    }

    public function test_application_layer_database_query_references_match_current_baseline(): void
    {
        $this->assertSame(
            [
                'app/Modules/Shared/Application/Service/NotificationReader.php' => [
                    'DB::',
                ],
                'app/Modules/Shared/Application/Service/NotificationWriter.php' => [
                    'DB::',
                ],
            ],
            $this->collectForbiddenReferences([
                'app/Modules/MasterAkademik/Application',
                'app/Modules/Shared/Application',
            ], '/DB::/')
        );
    }

    public function test_import_controllers_do_not_depend_on_excel_infrastructure(): void
    {
        $this->assertSame(
            [],
            $this->collectForbiddenReferences([
                'app/Modules/KelasKuliah/Presentation/Http/Controllers',
                'app/Modules/MasterAkademik/Presentation/Http/Controllers',
            ], '/Maatwebsite\\\\Excel|App\\\\Modules\\\\.*\\\\Infrastructure\\\\Import/')
        );
    }

    public function test_presentation_controllers_do_not_access_database_or_infrastructure_directly(): void
    {
        $this->assertSame(
            [],
            $this->collectForbiddenReferences([
                'app/Modules/Dashboard/Presentation/Http/Controllers',
                'app/Modules/Iam/Presentation/Http/Controllers',
                'app/Modules/KelasKuliah/Presentation/Http/Controllers',
                'app/Modules/Laporan/Presentation/Http/Controllers',
                'app/Modules/MasterAkademik/Presentation/Http/Controllers',
                'app/Modules/Penjadwalan/Presentation/Http/Controllers',
                'app/Modules/Resource/Presentation/Http/Controllers',
                'app/Modules/Shared/Presentation/Http/Controllers',
            ], '/DB::|App\\\\Modules\\\\.*\\\\Infrastructure\\\\/')
        );
    }

    /**
     * @param list<string> $relativeRoots
     * @return array<string, list<string>>
     */
    private function collectForbiddenReferences(array $relativeRoots, string $pattern): array
    {
        $matches = [];

        foreach ($relativeRoots as $relativeRoot) {
            foreach ($this->iteratePhpFiles($relativeRoot) as $path) {
                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }

                preg_match_all($pattern, $contents, $fileMatches);
                if (empty($fileMatches[0])) {
                    continue;
                }

                $relativePath = ltrim(str_replace('\\', '/', substr($path, strlen(realpath(self::ROOT)))), '/');
                $normalizedMatches = array_values(array_unique(array_map('trim', $fileMatches[0])));
                sort($normalizedMatches);
                $matches[$relativePath] = $normalizedMatches;
            }
        }

        ksort($matches);

        return $matches;
    }

    /**
     * @return list<string>
     */
    private function iteratePhpFiles(string $relativeRoot): array
    {
        $absoluteRoot = realpath(self::ROOT . '/' . $relativeRoot);
        if ($absoluteRoot === false) {
            return [];
        }

        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteRoot));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }
}
