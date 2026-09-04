<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

final class KelasKuliahClassLabel
{
    private function __construct(
        public readonly string $name,
        public readonly ?int $semester,
    ) {}

    public static function from(string $raw): self
    {
        preg_match('/SMT\s*(\d+)/i', $raw, $matches);

        return new self(
            name: trim((string) preg_replace('/\s*\[.*\]/', '', $raw)),
            semester: isset($matches[1]) ? (int) $matches[1] : null,
        );
    }
}
