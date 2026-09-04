<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormRequestDuplicationTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function requestPairsWithSharedRules(): array
    {
        return [
            'kelas store' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Requests/KelasStoreRequest.php',
                'KelasRules::rules();',
            ],
            'kelas update' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Requests/KelasUpdateRequest.php',
                'KelasRules::rules();',
            ],
            'kelas kuliah store' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Requests/KelasKuliahStoreRequest.php',
                'KelasKuliahRules::rules();',
            ],
            'kelas kuliah update' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Requests/KelasKuliahUpdateRequest.php',
                'KelasKuliahRules::rules();',
            ],
            'hari store' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/HariStoreRequest.php',
                'HariRules::rules();',
            ],
            'hari update' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/HariUpdateRequest.php',
                'HariRules::rules();',
            ],
            'ruangan store' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/RuanganStoreRequest.php',
                'RuanganRules::rules();',
            ],
            'ruangan update' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/RuanganUpdateRequest.php',
                'RuanganRules::rules();',
            ],
            'slot store' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/SlotStoreRequest.php',
                'SlotRules::rules();',
            ],
            'slot update' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/SlotUpdateRequest.php',
                'SlotRules::rules();',
            ],
            'waktu store' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/WaktuStoreRequest.php',
                'WaktuRules::rules();',
            ],
            'waktu update' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Requests/WaktuUpdateRequest.php',
                'WaktuRules::rules();',
            ],
            'jadwal store' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Requests/JadwalStoreRequest.php',
                'JadwalRules::rules();',
            ],
            'jadwal update' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Requests/JadwalUpdateRequest.php',
                'JadwalRules::rules();',
            ],
            'dosen controller' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/DosenController.php',
                'DosenRules::rules()',
            ],
            'mata kuliah controller' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/MataKuliahController.php',
                'MataKuliahRules::rules()',
            ],
            'jurusan controller' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/JurusanController.php',
                'JurusanRules::rules()',
            ],
            'program studi controller' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/ProgramStudiController.php',
                'ProgramStudiRules::rules()',
            ],
        ];
    }

    #[DataProvider('requestPairsWithSharedRules')]
    public function test_requests_delegate_duplicate_rules_to_shared_rule_objects(string $path, string $expectedCall): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString($expectedCall, $contents);
    }

    public function test_paginated_master_lists_use_shared_formatter(): void
    {
        foreach ([
            __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/DosenController.php',
            __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/MataKuliahController.php',
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);

            $this->assertStringContainsString('PagedResponseFormatter::format(', $contents);
        }
    }

    public function test_resources_delegate_pagination_to_shared_formatter(): void
    {
        foreach ([
            __DIR__ . '/../../../app/Modules/Iam/Presentation/Http/Resources/UserResource.php' => 'PagedResponseFormatter::format(',
            __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Resources/KelasResource.php' => 'PagedResponseFormatter::format(',
            __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Resources/KelasKuliahCollectionResource.php' => 'PagedResponseFormatter::format(',
            __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Resources/HariResource.php' => 'PagedResponseFormatter::formatNested(',
            __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Resources/RuanganResource.php' => 'PagedResponseFormatter::formatNested(',
            __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Resources/SlotResource.php' => 'PagedResponseFormatter::formatNested(',
            __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Resources/WaktuResource.php' => 'PagedResponseFormatter::formatNested(',
            __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php' => 'PagedResponseFormatter::formatNested(',
        ] as $path => $expectedCall) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);

            $this->assertStringContainsString($expectedCall, $contents);
        }
    }
}
