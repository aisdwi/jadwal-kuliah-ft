<?php

namespace App\Modules\Resource\Presentation\Http\Controllers;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Resource\Application\Service\RuanganAppService;
use App\Modules\Resource\Presentation\Http\Requests\RuanganStoreRequest;
use App\Modules\Resource\Presentation\Http\Requests\RuanganUpdateRequest;
use App\Modules\Resource\Presentation\Http\Resources\RuanganResource;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuanganController
{
    public function __construct(
        protected RuanganAppService $service,
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $jurusanId = $request->integer('jurusan_id') ?: null;
        $data = $this->service->list($search, $jurusanId);
        return response()->json(RuanganResource::pagedToArray($data));
    }

    public function store(RuanganStoreRequest $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $ruangan = $this->service->persist($request->validated());
        $this->activityLogger->log(
            action: 'create',
            subjectType: 'Ruangan',
            subjectName: (string) $ruangan->ruangan,
            description: 'Menambahkan ruangan ' . $ruangan->ruangan,
        );
        return response()->json(RuanganResource::toArray($ruangan), 201);
    }

    public function show(int $id): JsonResponse
    {
        $ruangan = $this->service->findById($id);
        return response()->json(RuanganResource::toArray($ruangan));
    }

    public function update(RuanganUpdateRequest $request, int $id): JsonResponse
    {
        $this->authorizeWrite($request);

        $ruangan = $this->service->persist($request->validated(), $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: 'Ruangan',
            subjectName: (string) $ruangan->ruangan,
            description: 'Memperbarui ruangan ' . $ruangan->ruangan,
        );
        return response()->json(RuanganResource::toArray($ruangan));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorizeWrite(request());

        $ruangan = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: 'Ruangan',
            subjectName: (string) $ruangan->ruangan,
            description: 'Menghapus ruangan ' . $ruangan->ruangan,
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $data = $this->service->list();
        $deletedCount = BulkDeleteResource::delete($data, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Ruangan',
            subjectName: 'Semua Ruangan',
            description: "Menghapus {$deletedCount} data ruangan",
        );

        return response()->json([
            'message' => 'Data Ruangan berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function authorizeWrite(Request $request): void
    {
        $user = $request->user();
        $roleName = RoleName::normalize($user?->role?->role ?? null);
        $allowedByName = in_array($roleName, ['Super Admin', 'Admin Fakultas', 'Admin Jurusan'], true);
        $allowedByPolicy = $user ? PermissionPolicy::can((int) $user->role_id, PermissionPolicy::MASTER_WRITE) : false;

        abort_unless($allowedByName || $allowedByPolicy, 403);
    }
}
