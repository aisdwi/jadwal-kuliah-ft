<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class JadwalAssignmentWriter
{
    public function __construct(
        private readonly JadwalRepository $repo,
        private readonly KelasKuliahRepository $kelasKuliahRepo,
        private readonly JadwalAssignmentValidator $assignmentValidator,
    ) {}

    public function persist(array $data, ?int $id = null): mixed
    {
        $request = JadwalAssignmentRequest::fromArray($data);
        $existingJadwal = $this->repo->findByKelasKuliah($request->kelasKuliahId);
        $kelasKuliah = $this->kelasKuliahOrFail($request->kelasKuliahId);

        if ($request->isClearAssignment()) {
            $this->repo->deleteByKelasKuliah($request->kelasKuliahId);
            return null;
        }

        if ($request->isCompleteAssignment()) {
            $this->assignmentValidator->validate($kelasKuliah, $request->slotId, $request->ruanganId);
        }

        return $this->saveAssignment($request->payload(), $id, $existingJadwal);
    }

    private function kelasKuliahOrFail(int $kelasKuliahId): object
    {
        return $this->kelasKuliahRepo->findById($kelasKuliahId)
            ?? abort(404, 'Kelas kuliah tidak ditemukan atau tidak dapat diakses');
    }

    private function saveAssignment(array $payload, ?int $id, ?object $existingJadwal): mixed
    {
        try {
            if ($existingJadwal && $id === null) {
                return $this->repo->update($existingJadwal->id, $payload);
            }

            return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
        } catch (QueryException $exception) {
            if ($this->isScheduleRoomSlotDuplicate($exception)) {
                throw new HttpException(422, 'Ruangan sudah digunakan pada slot waktu yang sama.', $exception);
            }

            throw $exception;
        }
    }

    private function isScheduleRoomSlotDuplicate(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'jadwal_slot_ruangan_unique')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'jadwal'));
    }
}
