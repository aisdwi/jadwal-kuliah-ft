<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Controllers;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use App\Modules\Penjadwalan\Application\Service\SchedulingAppService;
use App\Modules\Penjadwalan\Presentation\Http\Support\SchedulingActivity;
use App\Modules\Penjadwalan\Presentation\Http\Support\SchedulingGenerationRequestData;
use App\Modules\Penjadwalan\Presentation\Http\Support\SchedulingScopeData;
use App\Modules\Shared\Application\Service\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchedulingController
{
    public function __construct(
        protected SchedulingAppService $service,
        protected ActivityLogger $activityLogger,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $scope = SchedulingScopeData::scope($request);
        $programStudiId = SchedulingScopeData::programStudiId($scope);
        $semesterTipe = $request->query('semester_tipe');

        $result = $this->service->preview($programStudiId, $semesterTipe, $scope);

        return response()->json($result);
    }

    public function generate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PermissionPolicy::JADWAL_GENERATE), 403);

        $validated = $request->validate(SchedulingGenerationRequestData::rules());

        $scope = SchedulingScopeData::scope($request);
        $programStudiId = SchedulingScopeData::programStudiId($scope);
        $semesterTipe = $validated['semester_tipe'] ?? null;
        $params = SchedulingGenerationRequestData::params($validated);

        $result = $this->service->generate($programStudiId, $semesterTipe, $scope, $params);
        $this->activity()->logGenerate($semesterTipe);

        return response()->json($result);
    }

    public function cancel(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PermissionPolicy::JADWAL_GENERATE), 403);

        $result = $this->service->cancel();
        $this->activity()->log('cancel', 'Proses generate', 'Membatalkan proses generate jadwal otomatis');

        return response()->json($result);
    }

    public function restoreLast(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PermissionPolicy::JADWAL_GENERATE), 403);

        $scope = SchedulingScopeData::scope($request);
        $semesterTipe = $request->input('semester_tipe') ?? $request->query('semester_tipe');
        $result = $this->service->restoreLast($scope, is_string($semesterTipe) ? $semesterTipe : null);
        $this->activity()->log('restore', 'Hasil terakhir', 'Memulihkan hasil generate jadwal terakhir');

        return response()->json($result);
    }

    public function clearAll(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PermissionPolicy::JADWAL_GENERATE), 403);

        $scope = SchedulingScopeData::scope($request);
        $semesterTipe = $request->input('semester_tipe') ?? $request->query('semester_tipe');
        $result = $this->service->clearAll($scope, is_string($semesterTipe) ? $semesterTipe : null);
        $this->activity()->log('clear', 'Semua hasil generate', 'Menghapus seluruh hasil generate jadwal');

        return response()->json($result);
    }

    public function resetAuto(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PermissionPolicy::JADWAL_GENERATE), 403);

        $result = $this->service->resetAuto();
        $this->activity()->log('reset', 'Status otomatis', 'Mereset status generate jadwal otomatis');

        return response()->json($result);
    }

    public function progress(): JsonResponse
    {
        $userId = (string) auth()->id();

        $result = $this->service->progress($userId);

        return response()->json($result ?? ['status' => 'no_progress']);
    }

    private function activity(): SchedulingActivity
    {
        return new SchedulingActivity($this->activityLogger);
    }
}
