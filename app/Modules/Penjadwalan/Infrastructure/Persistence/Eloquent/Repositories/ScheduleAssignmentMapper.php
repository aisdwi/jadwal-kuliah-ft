<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Penjadwalan\Domain\Entities\ScheduleAssignment;
use App\Modules\Penjadwalan\Domain\Services\ScheduleTimeRangeParser;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;

final class ScheduleAssignmentMapper
{
    public function fromJadwal(JadwalModel $jadwal): ?ScheduleAssignment
    {
        $kelasKuliah = $jadwal->kelasKuliah;
        if (!$kelasKuliah) {
            return null;
        }

        return new ScheduleAssignment(
            kelasKuliahId: (int) $kelasKuliah->id,
            kelasId: (int) $kelasKuliah->kelas_id,
            slotId: (int) $jadwal->slot_id,
            ruanganId: (int) $jadwal->ruangan_id,
            lecturerIds: $this->lecturerIdsFor($kelasKuliah),
            dayId: $jadwal->slot?->hari_id === null ? null : (int) $jadwal->slot->hari_id,
            startMinute: $this->timeRange($jadwal)['start'] ?? null,
            endMinute: $this->timeRange($jadwal)['end'] ?? null,
        );
    }

    /**
     * @return array{start: int, end: int}|null
     */
    private function timeRange(JadwalModel $jadwal): ?array
    {
        return (new ScheduleTimeRangeParser())->parse($jadwal->slot?->waktu?->pukul);
    }

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
