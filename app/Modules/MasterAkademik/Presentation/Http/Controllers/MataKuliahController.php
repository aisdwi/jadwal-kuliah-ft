<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Controllers;

use App\Modules\MasterAkademik\Application\Port\MataKuliahImporterPort;
use App\Modules\MasterAkademik\Application\Service\MataKuliahAppService;
use App\Modules\MasterAkademik\Presentation\Http\Requests\MataKuliahRules;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MataKuliahController extends Controller
{
    public function __construct(
        private readonly MataKuliahAppService $service,
        private readonly ActivityLogger $activityLogger,
        private readonly MataKuliahImporterPort $importer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPageInput = $request->input('per_page', 'all');
        $result = $this->service->list(
            search:          $request->string('search', null)->toString() ?: null,
            programStudiId:  $request->filled('program_studi_id') ? (int) $request->program_studi_id : null,
            semester:        $request->filled('semester') ? (int) $request->semester : null,
            semesterTipe:    $request->input('semester_tipe'),
            filterByJurusan: ! $request->boolean('unscoped'),
            perPage:         $perPageInput === 'all' ? 'all' : max(1, (int) $perPageInput),
        );
        return response()->json(PagedResponseFormatter::format($result, fn ($m) => $this->formatItem($m)));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(MataKuliahRules::rules());

        $mk = $this->service->persist($v);
        return response()->json($this->formatItem($mk), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->formatItem($this->service->findById($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(MataKuliahRules::rules());

        $mk = $this->service->persist($v, $id);
        return response()->json($this->formatItem($mk));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $result = $this->service->list(
            search: null,
            programStudiId: $request->filled('program_studi_id') ? (int) $request->program_studi_id : null,
            semester: $request->filled('semester') ? (int) $request->semester : null,
            semesterTipe: $request->input('semester_tipe'),
            filterByJurusan: ! $request->boolean('unscoped'),
            perPage: 'all',
        );
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Mata Kuliah',
            subjectName: 'Semua Mata Kuliah',
            description: "Menghapus {$deletedCount} data mata kuliah",
        );

        return response()->json([
            'message' => 'Data Mata Kuliah berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    private function formatItem($m): array
    {
        return [
            'id'               => $m->id,
            'kode_mk'          => $m->kode_mk,
            'nama_mk'          => $m->nama_mk,
            'sks'              => $m->sks,
            'semester'         => $m->semester,
            'jurusan_id'       => $m->jurusan_id,
            'program_studi_id' => $m->program_studi_id,
            'jurusan'          => $m->jurusan ? ['id' => $m->jurusan_id, 'nama_jurusan' => $m->jurusan->nama_jurusan] : null,
            'program_studi'    => $m->programStudi ? ['id' => $m->program_studi_id, 'nama_prodi' => $m->programStudi->nama_prodi] : null,
        ];
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $this->importer->import($request->file('file'));
            $this->activityLogger->log(
                action: 'import',
                subjectType: 'Mata Kuliah',
                subjectName: 'Import Excel',
                description: 'Mengimpor data mata kuliah dari Excel',
            );
            return response()->json(['message' => 'Data Mata Kuliah berhasil diimpor']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
