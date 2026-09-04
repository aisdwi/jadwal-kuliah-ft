<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CoreDependencyBaselineTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../';
    private const SCAN_ROOTS = ['app', 'routes', 'tests', 'config'];
    private const EXCLUDED_SEGMENTS = [
        '/app/Core/',
        '/app/Infrastructure/Persistence/Models/',
        '/app/Infrastructure/Persistence/Repositories/',
    ];

    public function test_app_routes_tests_and_config_do_not_reference_core_namespace_outside_legacy_folder(): void
    {
        $this->assertSame(
            [],
            $this->collectForbiddenLines($this->namespacePattern(['App', 'Core']))
        );
    }

    public function test_app_routes_tests_and_config_do_not_reference_legacy_persistence_models(): void
    {
        $this->assertSame(
            [],
            $this->collectForbiddenLines($this->namespacePattern(['App', 'Infrastructure', 'Persistence', 'Models']))
        );
    }

    public function test_app_routes_tests_and_config_do_not_reference_legacy_persistence_repositories(): void
    {
        $this->assertSame(
            [],
            $this->collectForbiddenLines($this->namespacePattern(['App', 'Infrastructure', 'Persistence', 'Repositories']))
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function collectForbiddenLines(string $pattern): array
    {
        $matches = [];

        foreach ($this->iteratePhpFiles() as $path) {
            if ($path === realpath(__FILE__)) {
                continue;
            }

            $normalizedPath = str_replace('\\', '/', $path);
            foreach (self::EXCLUDED_SEGMENTS as $excludedSegment) {
                if (str_contains($normalizedPath, $excludedSegment)) {
                    continue 2;
                }
            }

            $contents = file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            preg_match_all($pattern, $contents, $fileMatches);
            if (empty($fileMatches[0])) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', substr($path, strlen(realpath(self::ROOT)))), '/');
            $normalizedMatches = array_map(
                static fn (string $match): string => trim($match),
                $fileMatches[0]
            );
            $normalizedMatches = array_values(array_unique($normalizedMatches));
            sort($normalizedMatches);
            $matches[$relativePath] = $normalizedMatches;
        }

        ksort($matches);

        return $matches;
    }

    private function namespacePattern(array $segments): string
    {
        $escapedNamespace = implode('\\\\', $segments);

        return '/^.*' . preg_quote($escapedNamespace, '/') . '.*$/m';
    }

    /**
     * @return list<string>
     */
    private function iteratePhpFiles(): array
    {
        $paths = [];

        foreach (self::SCAN_ROOTS as $root) {
            $absoluteRoot = realpath(self::ROOT . '/' . $root);
            if ($absoluteRoot === false) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteRoot));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $paths[] = $file->getPathname();
                }
            }
        }

        sort($paths);

        return $paths;
    }
}
