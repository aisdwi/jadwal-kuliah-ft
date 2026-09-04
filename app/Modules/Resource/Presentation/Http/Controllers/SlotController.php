<?php

namespace App\Modules\Resource\Presentation\Http\Controllers;

use App\Modules\Resource\Application\Service\SlotAppService;
use App\Modules\Resource\Presentation\Http\Requests\SlotStoreRequest;
use App\Modules\Resource\Presentation\Http\Requests\SlotUpdateRequest;
use App\Modules\Resource\Presentation\Http\Resources\SlotResource;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\AcademicScope;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController
{
    private const SUBJECT_TYPE = 'Slot Jadwal';

    public function __construct(
        protected SlotAppService $service,
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $hariId = $request->query('hari_id');
        $jurusanId = $request->integer('jurusan_id') ?: null;
        $scope = AcademicScope::fromUser($request->user());
        if ($jurusanId === null && !empty($scope['restrict_by_jurusan'])) {
            $jurusanId = $scope['jurusan_id'] ?? null;
        }

        if ($hariId) {
            $data = $this->service->findByHari((int) $hariId, $jurusanId);
            return response()->json(SlotResource::pagedToArray($data));
        }
        $data = $this->service->list($jurusanId);
        return response()->json(SlotResource::pagedToArray($data));
    }

    public function store(SlotStoreRequest $request): JsonResponse
    {
        $slot = $this->service->persist($request->validated());
        $slot = $this->service->findById($slot->id);
        $this->activityLogger->log(
            action: 'create',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($slot),
            description: 'Menambahkan slot jadwal ' . $this->formatSubjectName($slot),
        );
        return response()->json(SlotResource::toArray($slot), 201);
    }

    public function show(int $id): JsonResponse
    {
        $slot = $this->service->findById($id);
        return response()->json(SlotResource::toArray($slot));
    }

    public function update(SlotUpdateRequest $request, int $id): JsonResponse
    {
        $slot = $this->service->persist($request->validated(), $id);
        $slot = $this->service->findById($slot->id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($slot),
            description: 'Memperbarui slot jadwal ' . $this->formatSubjectName($slot),
        );
        return response()->json(SlotResource::toArray($slot));
    }

    public function destroy(int $id): JsonResponse
    {
        $slot = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($slot),
            description: 'Menghapus slot jadwal ' . $this->formatSubjectName($slot),
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $jurusanId = $request->integer('jurusan_id') ?: null;
        $scope = AcademicScope::fromUser($request->user());
        if ($jurusanId === null && !empty($scope['restrict_by_jurusan'])) {
            $jurusanId = $scope['jurusan_id'] ?? null;
        }

        $data = $this->service->list($jurusanId);
        $deletedCount = BulkDeleteResource::delete($data, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: self::SUBJECT_TYPE,
            subjectName: 'Semua Slot Jadwal',
            description: "Menghapus {$deletedCount} data slot jadwal",
        );

        return response()->json([
            'message' => 'Data Slot Jadwal berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function formatSubjectName($slot): string
    {
        $hari = trim((string) ($slot->hari?->nama_hari ?? ''));
        $pukul = trim((string) ($slot->waktu?->pukul ?? ''));

        $label = implode(' - ', array_values(array_filter([$hari, $pukul])));

        return $label !== '' ? $label : self::SUBJECT_TYPE;
    }
}
