<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;
use Illuminate\Support\Facades\DB;

final class SchedulingScheduleWriter
{
    public function updateSchedule(int $id, ?int $ruanganId, ?int $slotId): bool
    {
        if ($ruanganId === null || $slotId === null) {
            return JadwalModel::where('kelas_kuliah_id', $id)->delete() >= 0;
        }

        $jadwal = $this->latestJadwal($id);
        if ($jadwal) {
            $this->updateExisting($jadwal, $id, $ruanganId, $slotId);
            return true;
        }

        $this->createManual($id, $ruanganId, $slotId);

        return true;
    }

    public function persistGeneratedAssignments(array $assignments): void
    {
        logger()->info('scheduling_persist_generated_assignments', [
            'assignment_count' => count($assignments),
            'sample_kuliah' => array_slice(array_column($assignments, 'kuliah'), 0, 10),
            'sample_slots' => array_slice(array_column($assignments, 'slot'), 0, 10),
            'sample_ruang' => array_slice(array_column($assignments, 'ruang'), 0, 10),
            'scheduling_run_id' => $assignments[0]['scheduling_run_id'] ?? null,
        ]);

        DB::transaction(function () use ($assignments) {
            foreach ($assignments as $assignment) {
                $this->upsertGeneratedAssignment($assignment);
            }
        });
    }

    private function upsertGeneratedAssignment(array $assignment): void
    {
        $kelasKuliahId = (int) $assignment['kuliah'];
        if ($this->hasManualJadwal($kelasKuliahId)) {
            return;
        }

        $jadwal = $this->latestGeneratedJadwal($kelasKuliahId);
        $data = $this->generatedData($assignment);

        if ($jadwal) {
            $jadwal->update($data);
            $this->deleteDuplicates($kelasKuliahId, $jadwal->id);
            return;
        }

        JadwalModel::create($data + [
            'kelas_kuliah_id' => $kelasKuliahId,
            'created_at' => now(),
        ]);
    }

    private function latestJadwal(int $kelasKuliahId): ?JadwalModel
    {
        return JadwalModel::where('kelas_kuliah_id', $kelasKuliahId)->orderByDesc('id')->first();
    }

    private function latestGeneratedJadwal(int $kelasKuliahId): ?JadwalModel
    {
        return JadwalModel::where('kelas_kuliah_id', $kelasKuliahId)
            ->where('origin', 'generated')
            ->orderByDesc('id')
            ->first();
    }

    private function hasManualJadwal(int $kelasKuliahId): bool
    {
        return JadwalModel::where('kelas_kuliah_id', $kelasKuliahId)
            ->where('origin', 'manual')
            ->exists();
    }

    private function updateExisting(JadwalModel $jadwal, int $kelasKuliahId, int $ruanganId, int $slotId): void
    {
        $jadwal->update([
            'ruangan_id' => $ruanganId,
            'slot_id' => $slotId,
            'origin' => $jadwal->origin ?: 'manual',
        ]);

        $this->deleteDuplicates($kelasKuliahId, $jadwal->id);
    }

    private function createManual(int $kelasKuliahId, int $ruanganId, int $slotId): void
    {
        JadwalModel::create([
            'kelas_kuliah_id' => $kelasKuliahId,
            'ruangan_id' => $ruanganId,
            'slot_id' => $slotId,
            'origin' => 'manual',
        ]);
    }

    private function generatedData(array $assignment): array
    {
        return [
            'slot_id' => (int) $assignment['slot'],
            'ruangan_id' => (int) $assignment['ruang'],
            'origin' => 'generated',
            'scheduling_run_id' => empty($assignment['scheduling_run_id']) ? null : (int) $assignment['scheduling_run_id'],
            'updated_at' => now(),
        ];
    }

    private function deleteDuplicates(int $kelasKuliahId, int $keepId): void
    {
        JadwalModel::where('kelas_kuliah_id', $kelasKuliahId)
            ->where('id', '!=', $keepId)
            ->delete();
    }
}
