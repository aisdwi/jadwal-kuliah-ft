<?php

namespace App\Modules\Resource\Presentation\Http\Controllers;

use App\Modules\Resource\Application\Service\WaktuAppService;
use App\Modules\Resource\Presentation\Http\Requests\WaktuStoreRequest;
use App\Modules\Resource\Presentation\Http\Requests\WaktuUpdateRequest;
use App\Modules\Resource\Presentation\Http\Resources\WaktuResource;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;

class WaktuController
{
    private const SUBJECT_TYPE = 'Jam Kuliah';

    public function __construct(
        protected WaktuAppService $service,
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->service->list();
        return response()->json(WaktuResource::pagedToArray($data));
    }

    public function store(WaktuStoreRequest $request): JsonResponse
    {
        $waktu = $this->service->persist($request->validated());
        $this->activityLogger->log(
            action: 'create',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($waktu),
            description: 'Menambahkan jam kuliah ' . $this->formatSubjectName($waktu),
        );
        return response()->json(WaktuResource::toArray($waktu), 201);
    }

    public function show(int $id): JsonResponse
    {
        $waktu = $this->service->findById($id);
        return response()->json(WaktuResource::toArray($waktu));
    }

    public function update(WaktuUpdateRequest $request, int $id): JsonResponse
    {
        $waktu = $this->service->persist($request->validated(), $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($waktu),
            description: 'Memperbarui jam kuliah ' . $this->formatSubjectName($waktu),
        );
        return response()->json(WaktuResource::toArray($waktu));
    }

    public function destroy(int $id): JsonResponse
    {
        $waktu = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($waktu),
            description: 'Menghapus jam kuliah ' . $this->formatSubjectName($waktu),
        );
        return response()->json(null, 204);
    }

    public function destroyAll(): JsonResponse
    {
        $data = $this->service->list();
        $deletedCount = BulkDeleteResource::delete($data, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: self::SUBJECT_TYPE,
            subjectName: 'Semua Jam Kuliah',
            description: "Menghapus {$deletedCount} data jam kuliah",
        );

        return response()->json([
            'message' => 'Data Jam Kuliah berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function formatSubjectName($waktu): string
    {
        $pukul = trim((string) ($waktu->pukul ?? ''));
        $sks = $waktu->sks ?? null;

        if ($pukul !== '' && $sks !== null) {
            return "{$pukul} ({$sks} SKS)";
        }

        return $pukul !== '' ? $pukul : self::SUBJECT_TYPE;
    }
}
