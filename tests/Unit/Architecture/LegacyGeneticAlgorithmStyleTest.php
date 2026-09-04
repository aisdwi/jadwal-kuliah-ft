<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class LegacyGeneticAlgorithmStyleTest extends TestCase
{
    public function test_legacy_genetic_algorithm_methods_declare_visibility(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduling/Legacy/AlgoritmaGenetika.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        preg_match_all(
            '/^\s*(?!public\s+|protected\s+|private\s+)(?:static\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m',
            $contents,
            $matches,
        );

        $this->assertSame([], $matches[1]);
    }

    public function test_legacy_genetic_algorithm_avoids_high_noise_maintainability_smells(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduling/Legacy/AlgoritmaGenetika.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertDoesNotMatchRegularExpression('/public\s+\$[^;]+,\s*\$/', $contents);
        $this->assertDoesNotMatchRegularExpression('/catch\s*\([^)]*\)\s*\{\s*\}/', $contents);
        $this->assertDoesNotMatchRegularExpression('/\?.*:\s*\([^;?]+\?.*:/', $contents);
    }
}
