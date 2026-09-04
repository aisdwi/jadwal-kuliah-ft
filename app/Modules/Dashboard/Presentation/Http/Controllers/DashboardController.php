<?php

namespace App\Modules\Dashboard\Presentation\Http\Controllers;

use App\Modules\Dashboard\Application\Service\DashboardAppService;
use App\Modules\Shared\Application\Service\AcademicScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        protected DashboardAppService $service,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $scope = $this->buildScope($request);
        return response()->json($this->service->stats($scope));
    }

    public function chart(Request $request): JsonResponse
    {
        $scope = $this->buildScope($request);
        return response()->json($this->service->chart($scope));
    }

    public function activity(Request $request): JsonResponse
    {
        $scope = $this->buildScope($request);
        return response()->json($this->service->activity($scope, 10));
    }

    private function buildScope(Request $request): array
    {
        $user = $request->user();
        $scope = AcademicScope::fromUser($user);
        $scope['user_id'] = $user?->id;
        $semesterTipe = strtolower((string) $request->query('semester_tipe', ''));

        if (in_array($semesterTipe, ['ganjil', 'genap'], true)) {
            $scope['semester_tipe'] = $semesterTipe;
        }

        return $scope;
    }
}
