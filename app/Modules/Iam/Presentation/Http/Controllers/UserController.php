<?php

namespace App\Modules\Iam\Presentation\Http\Controllers;

use App\Modules\Iam\Application\Service\UserAppService;
use App\Modules\Iam\Presentation\Http\Requests\UserStoreRequest;
use App\Modules\Iam\Presentation\Http\Requests\UserUpdateRequest;
use App\Modules\Iam\Presentation\Http\Resources\UserResource;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserAppService $service,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPageInput = $request->input('per_page', 'all');
        $result = $this->service->list(
            search:  $request->string('search')->toString() ?: null,
            perPage: $perPageInput === 'all' ? 'all' : max(1, (int) $perPageInput),
        );
        return response()->json(UserResource::pagedToArray($result));
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $user = $this->service->persist($request->validated());
        $this->activityLogger->log(
            action: 'create',
            subjectType: 'Pengguna',
            subjectName: (string) $user->nama_user,
            description: 'Menambahkan pengguna ' . $user->nama_user,
        );
        return response()->json(UserResource::toArray($user), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(UserResource::toArray($this->service->findById($id)));
    }

    public function update(UserUpdateRequest $request, int $id): JsonResponse
    {
        $user = $this->service->persist($request->validated(), $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: 'Pengguna',
            subjectName: (string) $user->nama_user,
            description: 'Memperbarui pengguna ' . $user->nama_user,
        );
        return response()->json(UserResource::toArray($user));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: 'Pengguna',
            subjectName: (string) $user->nama_user,
            description: 'Menghapus pengguna ' . $user->nama_user,
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $currentUserId = (int) ($request->user()?->id ?? 0);
        $result = $this->service->list(search: null, perPage: 'all');
        $deletedCount = BulkDeleteResource::delete(
            $result,
            fn (int $id) => $this->service->delete($id),
            fn (mixed $item, int $id): bool => $id !== $currentUserId,
        );

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Pengguna',
            subjectName: 'Semua Pengguna',
            description: "Menghapus {$deletedCount} data pengguna",
        );

        return response()->json([
            'message' => 'Data Pengguna berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    public function roles(): JsonResponse
    {
        $roles = $this->service->listRoles();
        return response()->json(['data' => $roles]);
    }
}
