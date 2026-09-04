<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Controllers;

use App\Modules\Penjadwalan\Application\Service\JadwalAppService;
use App\Modules\Penjadwalan\Presentation\Http\Requests\JadwalStoreRequest;
use App\Modules\Penjadwalan\Presentation\Http\Requests\JadwalUpdateRequest;
use App\Modules\Penjadwalan\Presentation\Http\Resources\JadwalResource;
use App\Modules\Penjadwalan\Presentation\Http\Support\JadwalActivity;
use App\Modules\Shared\Application\Service\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JadwalController
{
    public function __construct(
        protected JadwalAppService $service,
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $programStudiId = $request->query('program_studi_id');
        $semester = $request->query('semester');
        $perPage = $request->query('per_page', 'all');

        $result = $this->service->list($programStudiId ? (int) $programStudiId : null, $semester ? (int) $semester : null, $perPage);

        return response()->json(JadwalResource::pagedToArray($result));
    }

    public function show(int $id): JsonResponse
    {
        $jadwal = $this->service->findById($id);

        return response()->json(JadwalResource::toArray($jadwal));
    }

    public function store(JadwalStoreRequest $request): JsonResponse
    {
        $jadwal = $this->service->persist($request->validated());
        $this->activity()->log('create', $jadwal, 'Menambahkan jadwal');

        return response()->json(JadwalResource::toArray($jadwal), 201);
    }

    public function update(JadwalUpdateRequest $request, int $id): JsonResponse
    {
        $jadwal = $this->service->persist($request->validated(), $id);
        $this->activity()->log('update', $jadwal, 'Memperbarui jadwal');

        return response()->json(JadwalResource::toArray($jadwal));
    }

    public function destroy(int $id): JsonResponse
    {
        $jadwal = $this->service->findById($id);
        $this->service->delete($id);
        $this->activity()->log('delete', $jadwal, 'Menghapus jadwal');

        return response()->json(['message' => 'Jadwal berhasil dihapus'], 204);
    }

    public function reassign(JadwalUpdateRequest $request, int $id): JsonResponse
    {
        $jadwal = $this->service->persist($request->validated(), $id);
        $this->activity()->log('update', $jadwal, 'Memindahkan jadwal');

        return response()->json(JadwalResource::toArray($jadwal));
    }

    public function manualAssign(JadwalStoreRequest $request, int $id): JsonResponse
    {
        $jadwal = $this->service->persist($request->validated() + ['kelas_kuliah_id' => $id, 'origin' => 'manual']);

        if ($jadwal === null) {
            $this->activity()->logClearedManualAssignment($id);
            return response()->json(['message' => 'Jadwal berhasil dihapus'], 200);
        }

        $this->activity()->log('create', $jadwal, 'Menetapkan jadwal');

        return response()->json(JadwalResource::toArray($jadwal), 201);
    }

    public function generate(Request $request): JsonResponse
    {
        $programStudiId = $request->input('program_studi_id');
        $semester = $request->input('semester');

        if (!$programStudiId) {
            return response()->json(['message' => 'program_studi_id diperlukan'], 422);
        }

        $this->service->deleteGeneratedByProgramStudi($programStudiId, $semester ? (int) $semester : null);
        $this->activity()->logGeneratedDeletion((int) $programStudiId, $semester);

        return response()->json(['message' => 'Jadwal sudah dihapus'], 200);
    }

    private function activity(): JadwalActivity
    {
        return new JadwalActivity($this->activityLogger);
    }
}
