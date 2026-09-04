<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Controllers;

use App\Modules\MasterAkademik\Application\Port\DosenImporterPort;
use App\Modules\MasterAkademik\Application\Service\DosenAppService;
use App\Modules\MasterAkademik\Presentation\Http\Requests\DosenRules;
use App\Modules\MasterAkademik\Presentation\Http\Resources\DosenPayload;
use App\Modules\MasterAkademik\Presentation\Http\Support\DosenIndexParams;
use App\Modules\MasterAkademik\Presentation\Http\Support\DosenSubjectFormatter;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DosenController extends Controller
{
    public function __construct(
        private readonly DosenAppService $service,
        private readonly ActivityLogger $activityLogger,
        private readonly DosenImporterPort $importer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $params = DosenIndexParams::fromRequest($request);

        $result = $this->service->list(
            search: $params->search,
            jurusanId: $params->jurusanId,
            filterByJurusan: $params->filterByJurusan,
            perPage: $params->perPage,
            excludeJurusanId: $params->excludeJurusanId,
        );

        return response()->json(PagedResponseFormatter::format($result, fn ($d) => DosenPayload::from($d)));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(DosenRules::rules());

        $dosen = $this->service->persist($v);
        $this->activityLogger->log(
            action: 'create',
            subjectType: 'Dosen',
            subjectName: DosenSubjectFormatter::format($dosen),
            description: 'Menambahkan dosen ' . DosenSubjectFormatter::format($dosen),
        );
        return response()->json(DosenPayload::from($dosen), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(DosenPayload::from($this->service->findById($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(DosenRules::rules());

        $dosen = $this->service->persist($v, $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: 'Dosen',
            subjectName: DosenSubjectFormatter::format($dosen),
            description: 'Memperbarui dosen ' . DosenSubjectFormatter::format($dosen),
        );
        return response()->json(DosenPayload::from($dosen));
    }

    public function destroy(int $id): JsonResponse
    {
        $dosen = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: 'Dosen',
            subjectName: DosenSubjectFormatter::format($dosen),
            description: 'Menghapus dosen ' . DosenSubjectFormatter::format($dosen),
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $params = DosenIndexParams::fromRequest($request);
        $result = $this->service->list(
            search: null,
            jurusanId: $params->jurusanId,
            filterByJurusan: $params->filterByJurusan,
            perPage: 'all',
            excludeJurusanId: $params->excludeJurusanId,
        );
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Dosen',
            subjectName: 'Semua Dosen',
            description: "Menghapus {$deletedCount} data dosen",
        );

        return response()->json([
            'message' => 'Data Dosen berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $this->importer->import($request->file('file'));
            $this->activityLogger->log(
                action: 'import',
                subjectType: 'Dosen',
                subjectName: 'Import Excel',
                description: 'Mengimpor data dosen dari Excel',
            );
            return response()->json(['message' => 'Data Dosen berhasil diimpor']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

}
