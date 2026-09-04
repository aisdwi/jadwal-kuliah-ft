<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Controllers;

use App\Modules\MasterAkademik\Application\Service\JurusanAppService;
use App\Modules\MasterAkademik\Presentation\Http\Requests\JurusanRules;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class JurusanController extends Controller
{
    public function __construct(
        private readonly JurusanAppService $service,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->list($request->input('search'));

        if ($items instanceof PagedResult) {
            $items = $items->items;
        } elseif (is_object($items) && method_exists($items, 'items')) {
            $items = $items->items();
        } else {
            $items = collect($items)->all();
        }

        return response()->json(['data' => array_map(fn ($j) => $this->fmt($j), $items)]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(JurusanRules::rules());
        $jurusan = $this->service->persist($v);
        $this->activityLogger->log(
            action: 'create',
            subjectType: 'Jurusan',
            subjectName: $jurusan->nama_jurusan,
            description: 'Menambahkan jurusan ' . $jurusan->nama_jurusan,
        );

        return response()->json($this->fmt($jurusan), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->fmt($this->service->findById($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(JurusanRules::rules());
        $jurusan = $this->service->persist($v, $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: 'Jurusan',
            subjectName: $jurusan->nama_jurusan,
            description: 'Memperbarui jurusan ' . $jurusan->nama_jurusan,
        );

        return response()->json($this->fmt($jurusan));
    }

    public function destroy(int $id): JsonResponse
    {
        $jurusan = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: 'Jurusan',
            subjectName: $jurusan->nama_jurusan,
            description: 'Menghapus jurusan ' . $jurusan->nama_jurusan,
        );
        return response()->json(null, 204);
    }

    public function destroyAll(): JsonResponse
    {
        $result = $this->service->list(perPage: 'all');
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Jurusan',
            subjectName: 'Semua Jurusan',
            description: "Menghapus {$deletedCount} data jurusan",
        );

        return response()->json([
            'message' => 'Data Jurusan berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function fmt($j): array
    {
        return ['id' => $j->id, 'nama_jurusan' => $j->nama_jurusan];
    }
}
