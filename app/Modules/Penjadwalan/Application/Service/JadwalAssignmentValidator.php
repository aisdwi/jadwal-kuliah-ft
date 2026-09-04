<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Domain\Entities\ScheduleAssignment;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Penjadwalan\Domain\Services\ScheduleConflictChecker;
use App\Modules\Penjadwalan\Domain\Services\ScheduleTimeRangeParser;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class JadwalAssignmentValidator
{
    public function __construct(
        private readonly JadwalRepository $repo,
        private ?ScheduleConflictChecker $conflictChecker = null,
    ) {}

    public function validate(object $kelasKuliah, int $slotId, int $ruanganId): void
    {
        $this->ensureAssignmentFitsCourse($kelasKuliah, $slotId, $ruanganId);
        $this->ensureAssignmentDoesNotConflict($kelasKuliah, $slotId, $ruanganId);
    }

    private function ensureAssignmentDoesNotConflict(object $kelasKuliah, int $slotId, int $ruanganId): void
    {
        $candidate = new ScheduleAssignment(
            kelasKuliahId: (int) $kelasKuliah->id,
            kelasId: (int) $kelasKuliah->kelas_id,
            slotId: $slotId,
            ruanganId: $ruanganId,
            lecturerIds: $this->lecturerIdsFor($kelasKuliah),
            dayId: $this->slotDayId($slotId),
            startMinute: $this->slotTimeRange($slotId)['start'] ?? null,
            endMinute: $this->slotTimeRange($slotId)['end'] ?? null,
        );

        $conflict = $this->conflictChecker()->firstConflict(
            $candidate,
            $this->repo->findAssignmentsBySlot($slotId, $candidate->kelasKuliahId),
        );

        if ($conflict !== null) {
            throw new HttpException(422, $conflict->message());
        }
    }

    private function ensureAssignmentFitsCourse(object $kelasKuliah, int $slotId, int $ruanganId): void
    {
        $slot = SlotModel::with(['waktu:id,sks,pukul', 'jurusans:id'])->find($slotId);
        $ruangan = RuanganModel::with('jurusans:id')->find($ruanganId);

        if (!$slot || !$ruangan) {
            throw new HttpException(422, 'Slot atau ruangan tidak ditemukan.');
        }

        $this->ensureSlotMatchesCourseSks($kelasKuliah, $slot);
        $this->ensureJurusanCanUseSlotAndRoom($kelasKuliah, $slot, $ruangan);
    }

    private function ensureSlotMatchesCourseSks(object $kelasKuliah, object $slot): void
    {
        $courseSks = (int) ($kelasKuliah->matakuliah?->sks ?? 0);
        $slotSks = (int) ($slot->waktu?->sks ?? 0);

        if ($courseSks > 0 && $slotSks > 0 && $courseSks !== $slotSks) {
            throw new HttpException(422, "Mata kuliah {$courseSks} SKS tidak dapat ditempatkan pada slot {$slotSks} SKS.");
        }
    }

    private function ensureJurusanCanUseSlotAndRoom(object $kelasKuliah, object $slot, object $ruangan): void
    {
        $jurusanId = $this->jurusanIdFor($kelasKuliah);
        if ($jurusanId === null) {
            return;
        }

        $this->ensureJurusanAllowed($jurusanId, $slot->jurusans->pluck('id')->all(), 'Slot jadwal tidak tersedia untuk jurusan mata kuliah ini.');
        $this->ensureJurusanAllowed($jurusanId, $ruangan->jurusans->pluck('id')->all(), 'Ruangan tidak tersedia untuk jurusan mata kuliah ini.');
    }

    private function ensureJurusanAllowed(int $jurusanId, array $allowedIds, string $message): void
    {
        $ids = array_map(fn ($id) => (int) $id, $allowedIds);
        if (!empty($ids) && !in_array($jurusanId, $ids, true)) {
            throw new HttpException(422, $message);
        }
    }

    private function jurusanIdFor(object $kelasKuliah): ?int
    {
        $jurusanId = $kelasKuliah->matakuliah?->jurusan_id
            ?? $kelasKuliah->matakuliah?->programStudi?->jurusan_id
            ?? $kelasKuliah->kelas?->jurusan_id
            ?? $kelasKuliah->kelas?->programStudi?->jurusan_id
            ?? null;

        return $jurusanId === null ? null : (int) $jurusanId;
    }

    private function conflictChecker(): ScheduleConflictChecker
    {
        return $this->conflictChecker ??= new ScheduleConflictChecker();
    }

    private function slotDayId(int $slotId): ?int
    {
        $slot = SlotModel::find($slotId, ['id', 'hari_id']);

        return $slot?->hari_id === null ? null : (int) $slot->hari_id;
    }

    /**
     * @return array{start: int, end: int}|null
     */
    private function slotTimeRange(int $slotId): ?array
    {
        $slot = SlotModel::with('waktu:id,pukul')->find($slotId, ['id', 'waktu_id']);

        return (new ScheduleTimeRangeParser())->parse($slot?->waktu?->pukul);
    }

    /**
     * @return list<int>
     */
    private function lecturerIdsFor(object $kelasKuliah): array
    {
        $ids = [];

        if (!empty($kelasKuliah->dosen_id)) {
            $ids[] = (int) $kelasKuliah->dosen_id;
        }

        foreach (($kelasKuliah->dosens ?? []) as $dosen) {
            if (!empty($dosen->id)) {
                $ids[] = (int) $dosen->id;
            }
        }

        return array_values(array_unique($ids));
    }
}
