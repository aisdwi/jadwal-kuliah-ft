<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Controllers;

use App\Modules\KelasKuliah\Application\Port\KelasImporterPort;
use App\Modules\KelasKuliah\Application\Service\KelasAppService;
use App\Modules\KelasKuliah\Presentation\Http\Requests\KelasStoreRequest;
use App\Modules\KelasKuliah\Presentation\Http\Requests\KelasUpdateRequest;
use App\Modules\KelasKuliah\Presentation\Http\Resources\KelasResource;
use App\Modules\Shared\Application\Service\ActivityLogger;
use App\Modules\Shared\Application\Service\BulkDeleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KelasController extends Controller
{
    public function __construct(
        private readonly KelasAppService $service,
        private readonly ActivityLogger $activityLogger,
        private readonly KelasImporterPort $importer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list(
            search: $request->input('search'),
            programStudiId: $request->integer('program_studi_id') ?: null,
            semester: $request->integer('semester') ?: null,
            perPage: $request->input('per_page', 'all'),
        );

        return response()->json(KelasResource::pagedToArray($result));
    }

    public function store(KelasStoreRequest $request): JsonResponse
    {
        $kelas = $this->service->persist($request->validated());
        $this->activityLogger->log(
            action: 'create',
            subjectType: 'Kelas',
            subjectName: $this->formatSubjectName($kelas),
            description: 'Menambahkan kelas ' . $this->formatSubjectName($kelas),
        );
        return response()->json(KelasResource::toArray($kelas), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(KelasResource::toArray($this->service->findById($id)));
    }

    public function update(KelasUpdateRequest $request, int $id): JsonResponse
    {
        $kelas = $this->service->persist($request->validated(), $id);
        $this->activityLogger->log(
            action: 'update',
            subjectType: 'Kelas',
            subjectName: $this->formatSubjectName($kelas),
            description: 'Memperbarui kelas ' . $this->formatSubjectName($kelas),
        );
        return response()->json(KelasResource::toArray($kelas));
    }

    public function destroy(int $id): JsonResponse
    {
        $kelas = $this->service->findById($id);
        $this->service->delete($id);
        $this->activityLogger->log(
            action: 'delete',
            subjectType: 'Kelas',
            subjectName: $this->formatSubjectName($kelas),
            description: 'Menghapus kelas ' . $this->formatSubjectName($kelas),
        );
        return response()->json(null, 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $result = $this->service->list(
            search: null,
            programStudiId: $request->integer('program_studi_id') ?: null,
            semester: $request->integer('semester') ?: null,
            perPage: 'all',
        );
        $deletedCount = BulkDeleteResource::delete($result, fn (int $id) => $this->service->delete($id));

        $this->activityLogger->log(
            action: 'delete_all',
            subjectType: 'Kelas',
            subjectName: 'Semua Kelas',
            description: "Menghapus {$deletedCount} data kelas",
        );

        return response()->json([
            'message' => 'Data Kelas berhasil dihapus',
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
                subjectType: 'Kelas',
                subjectName: 'Import Excel',
                description: 'Mengimpor data kelas dari Excel',
            );
            return response()->json(['message' => 'Data Kelas berhasil diimpor']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatSubjectName($kelas): string
    {
        $namaKelas = trim((string) ($kelas->nama_kelas ?? ''));
        $semester = $kelas->semester ?? null;

        if ($namaKelas !== '' && $semester !== null) {
            return "{$namaKelas} (Semester {$semester})";
        }

        return $namaKelas !== '' ? $namaKelas : 'Kelas';
    }
}
