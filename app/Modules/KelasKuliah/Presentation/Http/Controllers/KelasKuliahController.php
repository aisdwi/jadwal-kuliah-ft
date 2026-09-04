<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Controllers;

use App\Modules\KelasKuliah\Application\Port\KelasKuliahImporterPort;
use App\Modules\KelasKuliah\Application\Service\KelasKuliahAppService;
use App\Modules\KelasKuliah\Presentation\Http\Requests\KelasKuliahStoreRequest;
use App\Modules\KelasKuliah\Presentation\Http\Requests\KelasKuliahUpdateRequest;
use App\Modules\KelasKuliah\Presentation\Http\Resources\KelasKuliahCollectionResource;
use App\Modules\KelasKuliah\Presentation\Http\Resources\KelasKuliahResource;
use App\Modules\KelasKuliah\Presentation\Http\Support\KelasKuliahIndexFilters;
use App\Modules\KelasKuliah\Presentation\Http\Support\KelasKuliahSubjectFormatter;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KelasKuliahController extends Controller
{
    private const SUBJECT_TYPE = 'Kelas Kuliah';

    public function __construct(
        private readonly KelasKuliahAppService $service,
        private readonly ActivityLogger $activityLogger,
        private readonly KelasKuliahImporterPort $importer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list(KelasKuliahIndexFilters::fromRequest($request)->toArray());

        return response()->json(KelasKuliahCollectionResource::toArray($result));
    }

    public function store(KelasKuliahStoreRequest $request): JsonResponse
    {
        $kk = $this->service->persist($request->validated());
        $subjectName = KelasKuliahSubjectFormatter::format($kk);
        $this->activityLogger->log(
            action: 'create',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $subjectName,
            description: 'Menambahkan kelas kuliah ' . $subjectName,
        );
        return response()->json(KelasKuliahResource::toArray($kk), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(KelasKuliahResource::toArray($this->service->findById($id)));
    }

    public function update(KelasKuliahUpdateRequest $request, int $id): JsonResponse
    {
        $kk = $this->service->persist($request->validated(), $id);
        $subjectName = KelasKuliahSubjectFormatter::format($kk);
        $this->activityLogger->log(
            action: 'update',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $subjectName,
            description: 'Memperbarui kelas kuliah ' . $subjectName,
        );
        return response()->json(KelasKuliahResource::toArray($kk));
    }

    public function destroy(int $id): JsonResponse
    {
        $kk = $this->service->findById($id);
        $this->service->delete($id);
        $subjectName = KelasKuliahSubjectFormatter::format($kk);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $subjectName,
            description: 'Menghapus kelas kuliah ' . $subjectName,
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $filters = KelasKuliahIndexFilters::fromRequest($request)->toArray();
        $filters['per_page'] = 'all';
        unset($filters['search']);

        $result = $this->service->list($filters);
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: self::SUBJECT_TYPE,
            subjectName: 'Semua Kelas Kuliah',
            description: "Menghapus {$deletedCount} data kelas kuliah",
        );

        return response()->json([
            'message' => 'Data ' . self::SUBJECT_TYPE . ' berhasil dihapus',
            'deleted_count' => $deletedCount,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->service->stats(
            semesterTipe: $request->input('semester_tipe'),
            programStudiId: $request->integer('program_studi_id') ?: null,
            jurusanId: $request->integer('jurusan_id') ?: null,
        ));
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);

        try {
            $this->importer->import($request->file('file'));
            $this->activityLogger->log(
                action: 'import',
                subjectType: self::SUBJECT_TYPE,
                subjectName: 'Import Excel',
                description: 'Mengimpor data kelas kuliah dari Excel',
            );
            return response()->json(['message' => 'Data ' . self::SUBJECT_TYPE . ' berhasil diimpor']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

}
