<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class PhpMetricsHotspotRefactorTest extends TestCase
{
    public function test_academic_scope_delegates_payload_construction(): void
    {
        $contents = $this->contents('app/Modules/Shared/Application/Service/AcademicScope.php');

        $this->assertStringContainsString('AcademicScopePayload::make', $contents);
        $this->assertStringContainsString('AcademicScopeJurusanResolver::resolve', $contents);
        $this->assertStringNotContainsString('private static function jurusanId', $contents);
        $this->assertStringNotContainsString('applyToKelasKuliahQuery', $contents);
        $this->assertStringContainsString(
            'applyToKelasKuliahQuery',
            $this->contents('app/Modules/Shared/Application/Service/AcademicScopeQuery.php'),
        );
    }

    public function test_kelas_kuliah_model_delegates_academic_period_defaults(): void
    {
        $contents = $this->contents('app/Modules/KelasKuliah/Infrastructure/Persistence/Eloquent/Models/KelasKuliahModel.php');

        $this->assertStringContainsString('KelasKuliahAcademicPeriodDefaults::fill', $contents);
    }

    public function test_kelas_kuliah_repository_reuses_academic_period_defaults(): void
    {
        $contents = $this->contents('app/Modules/KelasKuliah/Infrastructure/Persistence/Eloquent/Repositories/EloquentKelasKuliahRepository.php');

        $this->assertStringContainsString('KelasKuliahAcademicPeriodDefaults::fillData', $contents);
        $this->assertStringNotContainsString('private function semesterTipeForMatakuliah', $contents);
    }

    public function test_kelas_kuliah_import_row_delegates_label_and_completeness_rules(): void
    {
        $row = $this->contents('app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImportRow.php');
        $factory = $this->contents('app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImportRowFactory.php');

        $this->assertStringContainsString('KelasKuliahClassLabel::from', $factory);
        $this->assertStringContainsString('KelasKuliahImportRowCompleteness::hasValues', $row);
    }

    public function test_scheduling_lifecycle_delegates_queue_and_result_recording(): void
    {
        $contents = $this->contents('app/Modules/Penjadwalan/Application/Service/SchedulingGenerationLifecycle.php');

        $this->assertStringContainsString('SchedulingQueueStarter', $contents);
        $this->assertStringContainsString('SchedulingGenerationRecorder', $contents);
        $this->assertStringContainsString('->start(', $contents);
        $this->assertStringContainsString('->recordCompleted(', $contents);
        $this->assertStringContainsString('->recordFailed(', $contents);
    }

    public function test_allocation_room_groups_delegate_classification_and_payload_mapping(): void
    {
        $contents = $this->contents('app/Modules/Resource/Application/Service/AllocationRoomGroups.php');

        $this->assertStringContainsString('new AllocationRoomClassifier', $contents);
        $this->assertStringContainsString('->integratedRooms', $contents);
        $this->assertStringContainsString('->kimiaFixedRooms', $contents);
        $this->assertStringContainsString('->variableRooms', $contents);
        $this->assertStringContainsString('AllocationRoomPayload::from', $contents);
        $this->assertStringNotContainsString('private static function isIntegratedRoom', $contents);
    }

    public function test_scheduling_controller_uses_scoped_request_helpers(): void
    {
        $contents = $this->contents('app/Modules/Penjadwalan/Presentation/Http/Controllers/SchedulingController.php');

        $this->assertStringContainsString('SchedulingScopeData::scope', $contents);
        $this->assertStringContainsString('SchedulingGenerationRequestData::rules', $contents);
        $this->assertStringNotContainsString('SchedulingRequestData', $contents);
    }

    public function test_notification_read_and_write_responsibilities_are_separated(): void
    {
        $notifier = $this->contents('app/Modules/Penjadwalan/Application/Service/SchedulingGenerationNotifier.php');
        $controller = $this->contents('app/Modules/Shared/Presentation/Http/Controllers/NotificationController.php');

        $this->assertStringContainsString('NotificationWriter', $notifier);
        $this->assertStringContainsString('NotificationReader', $controller);
        $this->assertStringContainsString('NotificationWriter', $controller);
        $this->assertStringNotContainsString('NotificationService', $controller);
    }

    public function test_result_progress_payload_delegates_value_lookup(): void
    {
        $contents = $this->contents('app/Modules/Penjadwalan/Application/Service/ResultSchedulingProgressPayload.php');

        $this->assertStringContainsString('SchedulingProgressValue::first', $contents);
        $this->assertStringNotContainsString('private static function firstValue', $contents);
    }

    public function test_dashboard_scoped_query_delegates_semester_filters(): void
    {
        $contents = $this->contents('app/Modules/Dashboard/Infrastructure/Persistence/Eloquent/Repositories/DashboardScopedQuery.php');

        $this->assertStringContainsString('DashboardSemesterScope::applyToMataKuliah', $contents);
        $this->assertStringContainsString('DashboardSemesterScope::applyToKelasKuliah', $contents);
        $this->assertStringNotContainsString('Schema::', $contents);
    }

    public function test_kelas_kuliah_resource_delegates_nested_serializers(): void
    {
        $contents = $this->contents('app/Modules/KelasKuliah/Presentation/Http/Resources/KelasKuliahResource.php');

        $this->assertStringContainsString('KelasKuliahDosenPayload::from', $contents);
        $this->assertStringContainsString('KelasKuliahTeachingTeamPayload::from', $contents);
        $this->assertStringContainsString('KelasKuliahMataKuliahPayload::from', $contents);
        $this->assertStringContainsString('KelasKuliahSlotPayload::from', $contents);
        $this->assertStringNotContainsString('private function dosensPayload', $contents);
    }

    public function test_kelas_kuliah_collection_and_import_traits_are_split_by_responsibility(): void
    {
        $controller = $this->contents('app/Modules/KelasKuliah/Presentation/Http/Controllers/KelasKuliahController.php');
        $validationTrait = $this->contents('app/Modules/Shared/Infrastructure/Import/ValidatesImportRows.php');
        $kelasImport = $this->contents('app/Modules/KelasKuliah/Infrastructure/Import/KelasImport.php');

        $this->assertStringContainsString('KelasKuliahCollectionResource::toArray', $controller);
        $this->assertStringNotContainsString('stringValue', $validationTrait);
        $this->assertStringContainsString('ReadsImportRowValues', $kelasImport);
        $this->assertStringContainsString('ChecksImportRowCompleteness', $kelasImport);
    }

    public function test_paged_formatter_and_request_values_delegate_to_focused_helpers(): void
    {
        $formatter = $this->contents('app/Modules/Shared/Presentation/Http/Support/PagedResponseFormatter.php');
        $dosen = $this->contents('app/Modules/MasterAkademik/Presentation/Http/Support/DosenIndexParams.php');
        $kelasKuliah = $this->contents('app/Modules/KelasKuliah/Presentation/Http/Support/KelasKuliahIndexFilters.php');
        $assignment = $this->contents('app/Modules/Penjadwalan/Application/Service/JadwalAssignmentRequest.php');

        $this->assertStringContainsString('FlatPagedResponseFormatter::format', $formatter);
        $this->assertStringContainsString('NestedPagedResponseFormatter::format', $formatter);
        $this->assertStringContainsString('DosenIndexRequestValue::perPage', $dosen);
        $this->assertStringContainsString('KelasKuliahScheduledFilter::fromRequest', $kelasKuliah);
        $this->assertStringContainsString('JadwalAssignmentValue::nullableInt', $assignment);
    }

    public function test_import_row_and_schedule_subject_delegate_parsing(): void
    {
        $import = $this->contents('app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImport.php');
        $row = $this->contents('app/Modules/KelasKuliah/Infrastructure/Import/KelasKuliahImportRow.php');
        $offering = $this->contents('app/Modules/KelasKuliah/Domain/Entities/KelasKuliahOffering.php');
        $subject = $this->contents('app/Modules/Penjadwalan/Presentation/Http/Support/JadwalSubjectFormatter.php');

        $this->assertStringContainsString('KelasKuliahImportRowFactory::fromArray', $import);
        $this->assertStringNotContainsString('public static function fromArray', $row);
        $this->assertStringNotContainsString('teachingAssignmentKeyFromLabels', $offering);
        $this->assertStringContainsString('JadwalCourseLabel::from', $subject);
        $this->assertStringContainsString('JadwalSlotLabel::from', $subject);
    }

    public function test_scheduling_filters_and_slot_regenerator_delegate_focused_policies(): void
    {
        $filter = $this->contents('app/Modules/Penjadwalan/Infrastructure/Scheduling/Repositories/SchedulingKelasKuliahFilter.php');
        $regenerator = $this->contents('app/Console/Commands/Scheduling/JurusanSlotRegenerator.php');

        $this->assertStringContainsString('SchedulingKelasKuliahSearchFilter', $filter);
        $this->assertStringContainsString('SchedulingKelasKuliahScheduleStatusFilter', $filter);
        $this->assertStringContainsString('SchedulingSemesterTypeFilter', $filter);
        $this->assertStringNotContainsString('Schema::', $filter);
        $this->assertStringContainsString('JurusanSlotScheduleCleaner', $regenerator);
        $this->assertStringContainsString('JurusanSlotTimeCatalog', $regenerator);
        $this->assertStringContainsString('JurusanSlotSessions::all', $regenerator);
    }

    public function test_scheduling_app_service_delegates_snapshot_workflow(): void
    {
        $contents = $this->contents('app/Modules/Penjadwalan/Application/Service/SchedulingAppService.php');

        $this->assertStringContainsString('SchedulingSnapshotManager', $contents);
        $this->assertStringContainsString('->restoreLast(', $contents);
        $this->assertStringContainsString('->clearAll(', $contents);
        $this->assertStringContainsString('->clearBeforeGeneration(', $contents);
        $this->assertStringNotContainsString('SchedulingSnapshotRepositoryPort', $contents);
        $this->assertStringNotContainsString('JadwalRepository', $contents);
    }

    private function contents(string $relativePath): string
    {
        $contents = file_get_contents(__DIR__ . '/../../../' . $relativePath);

        $this->assertIsString($contents);

        return $contents;
    }
}
