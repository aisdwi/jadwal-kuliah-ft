<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Controllers;

use App\Modules\MasterAkademik\Application\Service\ProgramStudiAppService;
use App\Modules\MasterAkademik\Presentation\Http\Requests\ProgramStudiRules;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use App\Modules\Shared\Domain\PagedResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProgramStudiController extends Controller
{
    private const SUBJECT_TYPE = 'Program Studi';

    public function __construct(
        private readonly ProgramStudiAppService $service,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->list(
            $request->input('search'),
            $request->filled('jurusan_id') ? (int) $request->jurusan_id : null,
        );

        if ($items instanceof PagedResult) {
            $items = $items->items;
        } elseif (is_object($items) && method_exists($items, 'items')) {
            $items = $items->items();
        } else {
            $items = collect($items)->all();
        }

        return response()->json(['data' => array_map(fn ($p) => $this->fmt($p), $items)]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(ProgramStudiRules::rules());
        $programStudi = $this->service->persist($v);
        $this->activityLogger->log(
            action: 'create',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $programStudi->nama_prodi,
            description: 'Menambahkan program studi ' . $programStudi->nama_prodi,
        );

        return response()->json($this->fmt($programStudi), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->fmt($this->service->findById($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(ProgramStudiRules::rules());
        $programStudi = $this->service->persist($v, $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $programStudi->nama_prodi,
            description: 'Memperbarui program studi ' . $programStudi->nama_prodi,
        );

        return response()->json($this->fmt($programStudi));
    }

    public function destroy(int $id): JsonResponse
    {
        $programStudi = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $programStudi->nama_prodi,
            description: 'Menghapus program studi ' . $programStudi->nama_prodi,
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $result = $this->service->list(
            search: null,
            jurusanId: $request->filled('jurusan_id') ? (int) $request->jurusan_id : null,
            perPage: 'all',
        );
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: self::SUBJECT_TYPE,
            subjectName: 'Semua Program Studi',
            description: "Menghapus {$deletedCount} data program studi",
        );

        return response()->json([
            'message' => 'Data Program Studi berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function fmt($p): array
    {
        return [
            'id'          => $p->id,
            'nama_prodi'  => $p->nama_prodi,
            'jurusan_id'  => $p->jurusan_id,
            'jurusan'     => $p->jurusan ? ['id' => $p->jurusan_id, 'nama_jurusan' => $p->jurusan->nama_jurusan] : null,
        ];
    }
}
