<?php

namespace App\Console\Commands\Scheduling;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class JurusanSlotRegenerator
{
    private const BREAK_MINUTES = 10;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function regenerate(): array
    {
        (new JurusanSlotScheduleCleaner())->clear();

        $days = DB::table('hari')->orderBy('id')->get(['id', 'nama_hari']);
        $timesBySks = (new JurusanSlotTimeCatalog())->bySks();
        $sksDemandByJurusan = $this->sksDemandByJurusan();

        return DB::table('jurusan')
            ->orderBy('id')
            ->get(['id', 'nama_jurusan'])
            ->map(fn ($jurusan): array => $this->regenerateJurusan($jurusan, $days, $timesBySks, $sksDemandByJurusan))
            ->all();
    }

    private function regenerateJurusan(object $jurusan, Collection $days, Collection $timesBySks, Collection $sksDemandByJurusan): array
    {
        $demand = $sksDemandByJurusan->get((int) $jurusan->id, collect());
        $sksValues = $demand->keys()->map(fn ($sks) => (int) $sks)->values();
        $createdForJurusan = $this->createSlotsForJurusan((int) $jurusan->id, $demand, $days, $timesBySks);

        return [
            'jurusan' => $jurusan->nama_jurusan,
            'sks' => $sksValues->implode(', '),
            'slot' => $createdForJurusan,
            'catatan' => $this->slotGenerationNote($sksValues, $timesBySks),
        ];
    }

    private function createSlotsForJurusan(int $jurusanId, Collection $demand, Collection $days, Collection $timesBySks): int
    {
        if ($demand->isEmpty()) {
            return 0;
        }

        $created = 0;
        $originalDemand = $demand->mapWithKeys(fn ($count, $sks) => [(int) $sks => (int) $count])->all();
        $remainingDemand = $originalDemand;

        foreach ($days as $dayIndex => $day) {
            foreach (JurusanSlotSessions::all() as $sessionIndex => $session) {
                $created += $this->createSessionSlots(
                    $jurusanId,
                    (int) $day->id,
                    (int) $dayIndex,
                    (int) $sessionIndex,
                    $session['start'],
                    $session['end'],
                    $originalDemand,
                    $remainingDemand,
                    $timesBySks,
                );
            }
        }

        return $created;
    }

    private function createSessionSlots(
        int $jurusanId,
        int $dayId,
        int $dayIndex,
        int $sessionIndex,
        int $sessionStart,
        int $sessionEnd,
        array $originalDemand,
        array &$remainingDemand,
        Collection $timesBySks,
    ): int
    {
        $created = 0;
        $selectedRanges = [];
        $priority = $this->rotatedSksPriority($originalDemand, $dayIndex, $sessionIndex);

        while (true) {
            $sks = $this->nextSksWithAvailableTime($remainingDemand, $priority, $timesBySks, $selectedRanges);
            if ($sks === null) {
                break;
            }

            $time = $this->firstAvailableTime($timesBySks->get($sks, collect()), $selectedRanges);
            if ($time === null) {
                unset($remainingDemand[$sks]);
                continue;
            }

            $waktuId = (int) $time->id;
            $slotId = $this->firstOrCreateSlot($dayId, $waktuId);
            $this->attachJurusanSlot($jurusanId, $slotId);
            $selectedRanges[] = ['start' => (int) $time->start, 'end' => (int) $time->end];
            $created++;
        }

        return $created;
    }

    private function sksDemandByJurusan(): Collection
    {
        return DB::table('matakuliah')
            ->select('jurusan_id', 'sks', DB::raw('COUNT(*) as total'))
            ->whereNotNull('jurusan_id')
            ->whereNotNull('sks')
            ->where('sks', '>', 0)
            ->groupBy('jurusan_id', 'sks')
            ->orderBy('jurusan_id')
            ->orderBy('sks')
            ->get()
            ->groupBy(fn ($row) => (int) $row->jurusan_id)
            ->map(fn (Collection $rows) => $rows->mapWithKeys(fn ($row) => [(int) $row->sks => (int) $row->total]));
    }

    private function nextSksWithAvailableTime(
        array &$remainingDemand,
        array $priority,
        Collection $timesBySks,
        array $selectedRanges,
    ): ?int
    {
        $candidates = $this->availableSksValues($remainingDemand, $timesBySks, $selectedRanges);
        if ($candidates === []) {
            return null;
        }

        $rank = array_flip($priority);
        usort($candidates, function (int $a, int $b) use ($rank, $remainingDemand): int {
            $byPriority = ($rank[$a] ?? PHP_INT_MAX) <=> ($rank[$b] ?? PHP_INT_MAX);
            if ($byPriority !== 0) {
                return $byPriority;
            }

            $byDemand = ($remainingDemand[$b] ?? 0) <=> ($remainingDemand[$a] ?? 0);

            return $byDemand !== 0 ? $byDemand : $b <=> $a;
        });

        $sks = $candidates[0];
        $remainingDemand[$sks] = max(0, ($remainingDemand[$sks] ?? 0) - 1);
        if ($remainingDemand[$sks] === 0) {
            unset($remainingDemand[$sks]);
        }

        return $sks;
    }

    /**
     * @return list<int>
     */
    private function availableSksValues(array $demand, Collection $timesBySks, array $selectedRanges): array
    {
        return array_values(array_filter(
            array_keys($demand),
            fn (int $sks): bool => ($demand[$sks] ?? 0) > 0
                && $this->firstAvailableTime($timesBySks->get($sks, collect()), $selectedRanges) !== null,
        ));
    }

    private function firstAvailableTime(Collection $times, array $selectedRanges): ?object
    {
        foreach ($times as $time) {
            if (!$this->overlapsAny((int) $time->start, (int) $time->end, $selectedRanges)) {
                return $time;
            }
        }

        return null;
    }

    private function overlapsAny(int $start, int $end, array $selectedRanges): bool
    {
        foreach ($selectedRanges as $range) {
            if ($start < ($range['end'] + self::BREAK_MINUTES) && $range['start'] < ($end + self::BREAK_MINUTES)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function rotatedSksPriority(array $demand, int $dayIndex, int $sessionIndex): array
    {
        $values = array_map('intval', array_keys($demand));
        rsort($values);

        if ($values === []) {
            return [];
        }

        $rotation = ($dayIndex + $sessionIndex) % count($values);

        return array_values(array_merge(
            array_slice($values, $rotation),
            array_slice($values, 0, $rotation),
        ));
    }

    private function slotGenerationNote(Collection $sksValues, Collection $timesBySks): string
    {
        if ($sksValues->isEmpty()) {
            return 'Tidak ada mata kuliah';
        }

        $missing = $sksValues
            ->filter(fn (int $sks): bool => $timesBySks->get($sks, collect())->isEmpty())
            ->values();

        return $missing->isEmpty()
            ? '-'
            : 'Belum ada jam kuliah valid untuk SKS: ' . $missing->implode(', ');
    }

    private function firstOrCreateSlot(int $hariId, int $waktuId): int
    {
        $existingId = DB::table('slot')
            ->where('hari_id', $hariId)
            ->where('waktu_id', $waktuId)
            ->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) DB::table('slot')->insertGetId([
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachJurusanSlot(int $jurusanId, int $slotId): void
    {
        DB::table('jurusan_slot')->updateOrInsert(
            [
                'jurusan_id' => $jurusanId,
                'slot_id' => $slotId,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
