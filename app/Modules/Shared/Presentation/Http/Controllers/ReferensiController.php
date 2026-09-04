<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Modules\Iam\Application\Service\UserAppService;
use App\Modules\MasterAkademik\Application\Service\JurusanAppService;
use App\Modules\MasterAkademik\Application\Service\ProgramStudiAppService;
use App\Modules\Resource\Application\Service\GedungAppService;
use App\Modules\Resource\Application\Service\HariAppService;
use App\Modules\Resource\Application\Service\SlotAppService;
use App\Modules\Resource\Application\Service\WaktuAppService;
use App\Modules\Shared\Application\Service\AcademicScope;
use App\Modules\Shared\Presentation\Http\Resources\GedungReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\HariReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\JurusanReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\ProgramStudiReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\RoleReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\SlotReferencePayload;
use App\Modules\Shared\Presentation\Http\Resources\WaktuReferencePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReferensiController extends Controller
{
    public function __construct(
        private readonly JurusanAppService      $jurusanService,
        private readonly ProgramStudiAppService $prodiService,
        private readonly GedungAppService       $gedungService,
        private readonly HariAppService         $hariService,
        private readonly WaktuAppService        $waktuService,
        private readonly SlotAppService         $slotService,
        private readonly UserAppService         $userService,
    ) {}

    public function jurusan(): JsonResponse
    {
        return $this->jsonItems($this->jurusanService->list(), fn ($jurusan) => JurusanReferencePayload::from($jurusan));
    }

    public function programStudi(Request $request): JsonResponse
    {
        $jurusanId = $request->integer('jurusan_id') ?: null;
        $scope = AcademicScope::fromUser($request->user());

        if (!empty($scope['restrict_by_program_studi']) && !empty($scope['program_studi_id'])) {
            $programStudi = $this->prodiService->findById((int) $scope['program_studi_id']);
            return $this->jsonItems([$programStudi], fn ($programStudi) => ProgramStudiReferencePayload::from($programStudi));
        }

        if ($jurusanId === null && !empty($scope['restrict_by_jurusan'])) {
            $jurusanId = $scope['jurusan_id'] ?? null;
        }

        return $this->jsonItems($this->prodiService->list(jurusanId: $jurusanId), fn ($programStudi) => ProgramStudiReferencePayload::from($programStudi));
    }

    public function gedung(): JsonResponse
    {
        return $this->jsonItems($this->gedungService->list(), fn ($gedung) => GedungReferencePayload::from($gedung));
    }

    public function hari(): JsonResponse
    {
        return $this->jsonItems($this->hariService->list(), fn ($hari) => HariReferencePayload::from($hari));
    }

    public function waktu(): JsonResponse
    {
        return $this->jsonItems($this->waktuService->list(), fn ($waktu) => WaktuReferencePayload::from($waktu));
    }

    public function slot(Request $request): JsonResponse
    {
        $jurusanId = $request->integer('jurusan_id') ?: null;
        $scope = AcademicScope::fromUser($request->user());
        if ($jurusanId === null && !empty($scope['restrict_by_jurusan'])) {
            $jurusanId = $scope['jurusan_id'] ?? null;
        }

        $items = $this->slotService->list($jurusanId);
        return $this->jsonItems($items, fn ($slot) => SlotReferencePayload::from($slot));
    }

    public function roles(): JsonResponse
    {
        return $this->jsonItems($this->userService->listRoles(), fn ($role) => RoleReferencePayload::from($role));
    }

    private function jsonItems(iterable $items, callable $formatItem): JsonResponse
    {
        return response()->json(
            collect($items)->map($formatItem)->values()->toArray()
        );
    }
}
