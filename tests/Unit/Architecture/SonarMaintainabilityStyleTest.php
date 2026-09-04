<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SonarMaintainabilityStyleTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function duplicatedLiteralChecks(): array
    {
        return [
            'kelas kuliah subject' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php',
                "'Kelas Kuliah'",
            ],
            'slot subject' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Controllers/SlotController.php',
                "'Slot Jadwal'",
            ],
            'waktu subject' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Controllers/WaktuController.php',
                "'Jam Kuliah'",
            ],
            'program studi subject' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/ProgramStudiController.php',
                "'Program Studi'",
            ],
            'mata kuliah subject' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Application/Service/MataKuliahAppService.php',
                "'Mata Kuliah'",
            ],
            'kelas kuliah integer rule' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Requests/KelasKuliahRules.php',
                "'required|integer|min:1'",
            ],
            'mata kuliah jurusan relation' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Infrastructure/Persistence/Eloquent/Repositories/EloquentMataKuliahRepository.php',
                "'jurusan:id,nama_jurusan'",
            ],
            'mata kuliah prodi relation' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Infrastructure/Persistence/Eloquent/Repositories/EloquentMataKuliahRepository.php',
                "'programStudi:id,nama_prodi'",
            ],
            'program studi jurusan relation' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Infrastructure/Persistence/Eloquent/Repositories/EloquentProgramStudiRepository.php',
                "'jurusan:id,nama_jurusan'",
            ],
            'scheduling timestamp format' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php',
                "'H:i:s'",
            ],
            'scheduling all data label' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php',
                "'Semua Data'",
            ],
            'generate jadwal subject' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Controllers/SchedulingController.php',
                "'Generate Jadwal'",
            ],
        ];
    }

    #[DataProvider('duplicatedLiteralChecks')]
    public function test_high_noise_literals_are_declared_once(string $path, string $literal): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertLessThanOrEqual(1, substr_count($contents, $literal));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function filesWithoutNestedTernary(): array
    {
        return [
            'kelas kuliah controller' => [__DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php'],
            'kelas kuliah resource' => [__DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Resources/KelasKuliahResource.php'],
            'slot controller' => [__DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Controllers/SlotController.php'],
            'dosen controller' => [__DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Controllers/DosenController.php'],
            'scheduling service' => [__DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php'],
            'legacy ga engine adapter' => [__DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduling/Services/LegacyGeneticAlgorithmEngine.php'],
            'jadwal controller' => [__DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Controllers/JadwalController.php'],
            'jadwal resource' => [__DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php'],
            'referensi controller' => [__DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Controllers/ReferensiController.php'],
        ];
    }

    #[DataProvider('filesWithoutNestedTernary')]
    public function test_nested_ternary_is_extracted(string $path): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertDoesNotMatchRegularExpression('/\?.*:\s*\([^;?]+\?.*:/', $contents);
    }

    public function test_laporan_filename_sanitizer_uses_concise_word_class(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Laporan/Application/Service/LaporanAppService.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString('[^A-Za-z0-9_]', $contents);
        $this->assertStringContainsString('/\W/', $contents);
    }

    public function test_academic_scope_has_no_unused_unrestricted_flag(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Application/Service/AcademicScope.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString('$isUnrestricted', $contents);
    }

    public function test_genetic_algorithm_adapter_result_persister_captures_only_used_variables(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduler/GeneticAlgorithmAdapter.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('function (array $gaAssignments) use (&$assignments)', $contents);
    }

    public function test_scheduling_progress_action_does_not_request_unused_dependency(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Controllers/SchedulingController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('public function progress(): JsonResponse', $contents);
    }

    public function test_scheduling_service_actions_drop_unused_program_studi_parameter(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('public function cancel(): array', $contents);
        $this->assertStringContainsString('public function restoreLast(array $scope, ?string $semesterTipe = null): array', $contents);
        $this->assertStringContainsString('public function clearAll(array $scope, ?string $semesterTipe = null): array', $contents);
        $this->assertStringContainsString('public function resetAuto(): array', $contents);
    }

    public function test_kelas_kuliah_resource_extracts_nested_relationship_payloads(): void
    {
        $path = __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Resources/KelasKuliahResource.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('KelasKuliahProgramStudiPayload::from', $contents);
        $this->assertStringContainsString('KelasKuliahSlotPayload::from', $contents);
    }

    public function test_jadwal_resource_extracts_nested_relationship_payloads(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JadwalKelasKuliahPayload::from', $contents);
        $this->assertStringContainsString('JadwalSlotPayload::from', $contents);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function resourceSerializerFiles(): array
    {
        return [
            'kelas kuliah resource' => [__DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Resources/KelasKuliahResource.php'],
            'jadwal resource' => [__DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php'],
        ];
    }

    #[DataProvider('resourceSerializerFiles')]
    public function test_resource_payload_helpers_share_instance_state(string $path): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('private function __construct', $contents);
        $this->assertStringContainsString('private function serialize(): array', $contents);
        $this->assertSame(0, substr_count($contents, 'private static function'));
    }

    public function test_paged_response_formatter_helpers_share_instance_state(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Support/PagedResponseFormatter.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('FlatPagedResponseFormatter::format', $contents);
        $this->assertStringContainsString('NestedPagedResponseFormatter::format', $contents);
    }

    public function test_jadwal_resource_extracts_program_studi_payload_without_nested_ternary(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString("'program_studi' => isset(\$kelas['program_studi']) ? [", $contents);
    }

    public function test_referensi_slot_payload_does_not_use_nested_ternary(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Controllers/ReferensiController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString("relationLoaded('jurusans') ? \$s->jurusans->pluck('id')->values()->all() : []", $contents);
    }

    public function test_jurusan_scope_filter_delegates_scope_resolution_and_query_application(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Application/Traits/FiltersByJurusan.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('private function scopedJurusanId', $contents);
        $this->assertStringContainsString('private function applyJurusanFilter', $contents);
    }

    public function test_room_allocation_policy_delegates_weight_and_leftover_logic(): void
    {
        $weightsPath = __DIR__ . '/../../../app/Modules/Resource/Domain/Services/RoomQuotaWeights.php';
        $leftoverPath = __DIR__ . '/../../../app/Modules/Resource/Domain/Services/RoomLeftoverDistributor.php';
        $weights = file_get_contents($weightsPath);
        $leftovers = file_get_contents($leftoverPath);
        $this->assertIsString($weights);
        $this->assertIsString($leftovers);

        $this->assertStringContainsString('private function demandWeights', $weights);
        $this->assertStringContainsString('public function distribute', $leftovers);
    }

    public function test_referensi_slot_uses_extracted_payload_formatter(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Controllers/ReferensiController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('SlotReferencePayload::from', $contents);
        $this->assertStringNotContainsString('private function slotItemPayload', $contents);
        $this->assertStringNotContainsString('private function slotJurusanIds', $contents);
    }

    public function test_referensi_controller_delegates_reference_payloads(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Controllers/ReferensiController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JurusanReferencePayload::from', $contents);
        $this->assertStringContainsString('ProgramStudiReferencePayload::from', $contents);
        $this->assertStringContainsString('GedungReferencePayload::from', $contents);
        $this->assertStringContainsString('HariReferencePayload::from', $contents);
        $this->assertStringContainsString('WaktuReferencePayload::from', $contents);
        $this->assertStringContainsString('RoleReferencePayload::from', $contents);
        $this->assertStringNotContainsString('RoleName::normalize', $contents);
    }

    public function test_scheduling_query_filters_are_split_by_responsibility(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduling/Repositories/SchedulingKelasKuliahFilter.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('SchedulingKelasKuliahSearchFilter', $contents);
        $this->assertStringContainsString('SchedulingKelasKuliahScheduleStatusFilter', $contents);
        $this->assertStringContainsString('SchedulingSemesterTypeFilter', $contents);
    }

    public function test_kelas_kuliah_list_filter_delegates_search_filtering(): void
    {
        $path = __DIR__ . '/../../../app/Modules/KelasKuliah/Infrastructure/Persistence/Eloquent/Repositories/KelasKuliahListFilter.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('KelasKuliahSearchFilter', $contents);
        $this->assertStringNotContainsString('private const SEARCH_RELATIONS', $contents);
        $this->assertStringNotContainsString('private function addRelationSearch', $contents);
    }

    public function test_kelas_kuliah_controller_delegates_index_filter_parsing(): void
    {
        $path = __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('KelasKuliahIndexFilters::fromRequest', $contents);
        $this->assertStringNotContainsString('FILTER_VALIDATE_BOOLEAN', $contents);
    }

    public function test_kelas_kuliah_controller_delegates_subject_formatting(): void
    {
        $path = __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('KelasKuliahSubjectFormatter::format', $contents);
        $this->assertStringNotContainsString('private function formatSubjectName', $contents);
    }

    public function test_jurusan_ruangan_assignment_is_split_by_responsibility(): void
    {
        $initialPath = __DIR__ . '/../../../app/Modules/Resource/Application/Service/JurusanRoomInitialAllocationBuilder.php';
        $distributorPath = __DIR__ . '/../../../app/Modules/Resource/Application/Service/JurusanVariableRoomDistributor.php';
        $sorterPath = __DIR__ . '/../../../app/Modules/Resource/Application/Service/AllocatedRoomSorter.php';
        $initial = file_get_contents($initialPath);
        $distributor = file_get_contents($distributorPath);
        $sorter = file_get_contents($sorterPath);
        $this->assertIsString($initial);
        $this->assertIsString($distributor);
        $this->assertIsString($sorter);

        $this->assertStringContainsString('public function build', $initial);
        $this->assertStringContainsString('public function distribute', $distributor);
        $this->assertStringContainsString('public function sort', $sorter);
    }

    public function test_scheduling_service_delegates_generation_lifecycle(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('SchedulingPreviewBuilder', $contents);
        $this->assertStringContainsString('SchedulingGenerationLifecycle', $contents);
        $this->assertStringNotContainsString('private function generationCompletionMessage', $contents);
    }

    public function test_scheduling_service_constructor_keeps_only_used_dependencies(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringNotContainsString('SchedulingMutationRepositoryPort', $contents);
        $this->assertStringNotContainsString('SchedulerPort', $contents);
    }

    public function test_excel_row_mapper_uses_row_instance_state(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Laporan/Infrastructure/Export/JadwalExcelRowMapper.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('private function __construct', $contents);
        $this->assertStringContainsString('private function buildRow', $contents);
        $this->assertStringContainsString('private readonly object $jadwal', $contents);
    }

    public function test_jadwal_resource_delegates_nested_payloads_to_dedicated_serializers(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalResource.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JadwalKelasKuliahPayload::from', $contents);
        $this->assertStringContainsString('JadwalSlotPayload::from', $contents);
        $this->assertStringContainsString('JadwalRuanganPayload::from', $contents);
        $this->assertStringNotContainsString('private function kelasKuliahPayload', $contents);
    }

    public function test_scheduling_generation_lifecycle_delegates_progress_payloads(): void
    {
        $starter = file_get_contents(__DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingQueueStarter.php');
        $recorder = file_get_contents(__DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingGenerationRecorder.php');
        $this->assertIsString($starter);
        $this->assertIsString($recorder);

        $this->assertStringContainsString('QueuedSchedulingProgressPayload::make', $starter);
        $this->assertStringContainsString('CanceledSchedulingProgressPayload::make', $starter);
        $this->assertStringContainsString('ResultSchedulingProgressPayload::make', $recorder);
        $this->assertStringContainsString('FailedSchedulingProgressPayload::make', $recorder);
        $this->assertStringContainsString('SchedulingGenerationStatus::fromResult', $recorder);
    }

    public function test_jadwal_app_service_delegates_assignment_validation(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/JadwalAppService.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JadwalAssignmentValidator', $contents);
        $this->assertStringNotContainsString('private function ensureAssignmentDoesNotConflict', $contents);
        $this->assertStringNotContainsString('private function ensureAssignmentFitsCourse', $contents);
    }

    public function test_jadwal_assignment_writer_delegates_request_parsing(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/JadwalAssignmentWriter.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JadwalAssignmentRequest::fromArray', $contents);
        $this->assertStringNotContainsString('private function nullableInt', $contents);
        $this->assertStringNotContainsString('private function payload', $contents);
    }

    public function test_jadwal_kelas_kuliah_payload_delegates_nested_payloads(): void
    {
        $path = __DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Resources/JadwalKelasKuliahPayload.php';
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString('JadwalDosenPayload::from', $contents);
        $this->assertStringContainsString('JadwalMataKuliahPayload::from', $contents);
        $this->assertStringContainsString('JadwalKelasPayload::from', $contents);
        $this->assertStringNotContainsString('private function dosenPayload', $contents);
        $this->assertStringNotContainsString('private function matakuliahPayload', $contents);
        $this->assertStringNotContainsString('private function kelasPayload', $contents);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function formatterMethodsWithLimitedReturns(): array
    {
        return [
            'kelas kuliah subject formatter' => [
                __DIR__ . '/../../../app/Modules/KelasKuliah/Presentation/Http/Support/KelasKuliahSubjectFormatter.php',
                'format',
            ],
            'dosen subject formatter' => [
                __DIR__ . '/../../../app/Modules/MasterAkademik/Presentation/Http/Support/DosenSubjectFormatter.php',
                'format',
            ],
            'slot subject formatter' => [
                __DIR__ . '/../../../app/Modules/Resource/Presentation/Http/Controllers/SlotController.php',
                'formatSubjectName',
            ],
            'scheduling timestamp formatter' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Application/Service/SchedulingPreviewBuilder.php',
                'formatTimestamp',
            ],
            'schedule conflict checker' => [
                __DIR__ . '/../../../app/Modules/Penjadwalan/Domain/Services/ScheduleConflictChecker.php',
                'firstConflict',
            ],
            'room allocation policy' => [
                __DIR__ . '/../../../app/Modules/Resource/Domain/Services/RoomAllocationPolicy.php',
                'allocateVariableRoomQuota',
            ],
            'prodi context middleware' => [
                __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Middleware/EnsureProdiContext.php',
                'handle',
            ],
            'paged response formatter dispatcher' => [
                __DIR__ . '/../../../app/Modules/Shared/Presentation/Http/Support/FlatPagedResponseFormatter.php',
                'formatResult',
            ],
        ];
    }

    #[DataProvider('formatterMethodsWithLimitedReturns')]
    public function test_formatter_methods_do_not_exceed_three_returns(string $path, string $method): void
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertLessThanOrEqual(3, $this->countReturnsInMethod($contents, $method));
    }

    private function countReturnsInMethod(string $contents, string $method): int
    {
        $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)[^{]*\{(?<body>.*?)\n    \}/s';
        $matched = preg_match($pattern, $contents, $matches);

        $this->assertSame(1, $matched, "Method {$method} was not found.");

        return substr_count($matches['body'], 'return ');
    }
}
