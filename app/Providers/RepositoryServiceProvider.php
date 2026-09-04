<?php

namespace App\Providers;

use App\Modules\Laporan\Application\Port\ExcelExporterPort;
use App\Modules\KelasKuliah\Application\Port\KelasImporterPort;
use App\Modules\KelasKuliah\Application\Port\KelasKuliahImporterPort;
use App\Modules\MasterAkademik\Application\Port\DosenImporterPort;
use App\Modules\MasterAkademik\Application\Port\MataKuliahImporterPort;
use App\Modules\Penjadwalan\Application\Port\GeneticAlgorithmEnginePort;
use App\Modules\Penjadwalan\Application\Port\SchedulerPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingJobDispatcherPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingMutationRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingQueryRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingRunRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingSnapshotRepositoryPort;
use App\Modules\Shared\Application\Port\ActivityLogRepositoryPort;
use App\Modules\Shared\Application\Port\CacheServiceInterface;
use App\Modules\Shared\Application\Port\ClockInterface;
use App\Modules\Shared\Application\Port\HashServiceInterface;
use App\Modules\Shared\Application\Port\TransactionServiceInterface;
use App\Modules\Dashboard\Domain\Repositories\DashboardRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahStatsRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\MasterAkademik\Domain\Repositories\DosenRepository;
use App\Modules\MasterAkademik\Domain\Repositories\JurusanRepository;
use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Resource\Domain\Repositories\HariRepository;
use App\Modules\Resource\Domain\Repositories\GedungRepository;
use App\Modules\Resource\Domain\Repositories\JurusanRuanganAllocationRepository;
use App\Modules\Resource\Domain\Repositories\RuanganRepository;
use App\Modules\Resource\Domain\Repositories\SlotRepository;
use App\Modules\Resource\Domain\Repositories\WaktuRepository;
use App\Modules\Laporan\Infrastructure\Export\MaatwebsiteExcelExporter;
use App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories\EloquentDashboardRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories\EloquentDosenRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentGedungRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentHariRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentJurusanRuanganAllocationRepository;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories\EloquentJadwalRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories\EloquentJurusanRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories\EloquentKelasKuliahRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories\EloquentKelasKuliahStatsRepository;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories\EloquentKelasRepository;
use App\Modules\KelasKuliah\Infrastructure\Import\KelasExcelImporter;
use App\Modules\KelasKuliah\Infrastructure\Import\KelasKuliahExcelImporter;
use App\Modules\MasterAkademik\Infrastructure\Import\DosenExcelImporter;
use App\Modules\MasterAkademik\Infrastructure\Import\MataKuliahExcelImporter;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories\EloquentMataKuliahRepository;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Repositories\EloquentProgramStudiRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentRuanganRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentSlotRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentWaktuRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduler\GeneticAlgorithmAdapter;
use App\Modules\Iam\Domain\Repositories\UserRepository;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Modules\Shared\Infrastructure\Services\LaravelActivityLogRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Keep release bindings explicit so module boundaries stay predictable.
        $this->app->bind(DosenRepository::class, EloquentDosenRepository::class);
        $this->app->bind(DosenImporterPort::class, DosenExcelImporter::class);
        $this->app->bind(MataKuliahRepository::class, EloquentMataKuliahRepository::class);
        $this->app->bind(MataKuliahImporterPort::class, MataKuliahExcelImporter::class);
        $this->app->bind(JurusanRepository::class, EloquentJurusanRepository::class);
        $this->app->bind(ProgramStudiRepository::class, EloquentProgramStudiRepository::class);

        $this->app->bind(GedungRepository::class, EloquentGedungRepository::class);
        $this->app->bind(RuanganRepository::class, EloquentRuanganRepository::class);
        $this->app->bind(JurusanRuanganAllocationRepository::class, EloquentJurusanRuanganAllocationRepository::class);
        $this->app->bind(HariRepository::class, EloquentHariRepository::class);
        $this->app->bind(WaktuRepository::class, EloquentWaktuRepository::class);
        $this->app->bind(SlotRepository::class, EloquentSlotRepository::class);

        $this->app->bind(UserRepository::class, EloquentUserRepository::class);

        $this->app->bind(KelasRepository::class, EloquentKelasRepository::class);
        $this->app->bind(KelasImporterPort::class, KelasExcelImporter::class);
        $this->app->bind(KelasKuliahRepository::class, EloquentKelasKuliahRepository::class);
        $this->app->bind(KelasKuliahStatsRepository::class, EloquentKelasKuliahStatsRepository::class);
        $this->app->bind(KelasKuliahImporterPort::class, KelasKuliahExcelImporter::class);

        $this->app->bind(DashboardRepository::class, EloquentDashboardRepository::class);
        $this->app->bind(ExcelExporterPort::class, MaatwebsiteExcelExporter::class);

        $this->app->bind(JadwalRepository::class, EloquentJadwalRepository::class);
        $this->app->bind(SchedulerPort::class, GeneticAlgorithmAdapter::class);
        $this->app->bind(SchedulingQueryRepositoryPort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingQueryRepository::class);
        $this->app->bind(SchedulingMutationRepositoryPort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingMutationRepository::class);
        $this->app->bind(SchedulingRunRepositoryPort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingRunRepository::class);
        $this->app->bind(SchedulingSnapshotRepositoryPort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingSnapshotRepository::class);
        $this->app->bind(SchedulingProgressStorePort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Services\DatabaseSchedulingProgressStore::class);
        $this->app->bind(SchedulingJobDispatcherPort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Services\LaravelSchedulingJobDispatcher::class);
        $this->app->bind(GeneticAlgorithmEnginePort::class, \App\Modules\Penjadwalan\Infrastructure\Scheduling\Services\LegacyGeneticAlgorithmEngine::class);

        $this->app->bind(ActivityLogRepositoryPort::class, LaravelActivityLogRepository::class);
        $this->app->bind(CacheServiceInterface::class, \App\Modules\Shared\Infrastructure\Services\LaravelCacheService::class);
        $this->app->bind(ClockInterface::class, \App\Modules\Shared\Infrastructure\Services\LaravelClock::class);
        $this->app->bind(HashServiceInterface::class, \App\Modules\Shared\Infrastructure\Services\LaravelHashService::class);
        $this->app->bind(TransactionServiceInterface::class, \App\Modules\Shared\Infrastructure\Services\LaravelTransactionService::class);
    }

    public function boot(): void
    {
        //
    }
}
