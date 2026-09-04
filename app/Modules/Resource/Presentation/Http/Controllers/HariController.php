<?php

namespace App\Modules\Resource\Presentation\Http\Controllers;

use App\Modules\Resource\Application\Service\HariAppService;
use App\Modules\Resource\Presentation\Http\Requests\HariStoreRequest;
use App\Modules\Resource\Presentation\Http\Requests\HariUpdateRequest;
use App\Modules\Resource\Presentation\Http\Resources\HariResource;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HariController
{
    public function __construct(protected HariAppService $service) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $data = $this->service->list($search);
        return response()->json(HariResource::pagedToArray($data));
    }

    public function store(HariStoreRequest $request): JsonResponse
    {
        $hari = $this->service->persist($request->validated());
        return response()->json(HariResource::toArray($hari), 201);
    }

    public function show(int $id): JsonResponse
    {
        $hari = $this->service->findById($id);
        return response()->json(HariResource::toArray($hari));
    }

    public function update(HariUpdateRequest $request, int $id): JsonResponse
    {
        $hari = $this->service->persist($request->validated(), $id);
        return response()->json(HariResource::toArray($hari));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function destroyAll(): JsonResponse
    {
        $data = $this->service->list();
        $deletedCount = BulkDeleteResource::delete($data, fn (int $id) => $this->service->delete($id));

        return response()->json([
            'message' => 'Data Hari berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }
}
