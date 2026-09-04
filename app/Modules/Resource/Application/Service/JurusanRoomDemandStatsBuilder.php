<?php

namespace App\Modules\Resource\Application\Service;

use Illuminate\Support\Collection;

final class JurusanRoomDemandStatsBuilder
{
    public function build(Collection $jurusans, Collection $demands, ?int $kimiaJurusanId, int $allocatableRoomCount, int $kimiaFixedRoomCount): array
    {
        $totals = $this->totals($demands);
        $stats = [];

        foreach ($jurusans as $jurusan) {
            $stats[$jurusan->id] = $this->statRow(
                $jurusan,
                $demands->get($jurusan->id),
                $totals,
                $kimiaJurusanId,
                $allocatableRoomCount,
                $kimiaFixedRoomCount,
            );
        }

        return $stats;
    }

    private function totals(Collection $demands): array
    {
        return [
            'classes' => max(1, (int) $demands->sum('total_classes')),
            'sks' => max(1, (int) $demands->sum('total_sks')),
            'students' => max(1, (int) $demands->sum('total_students')),
        ];
    }

    private function statRow(object $jurusan, ?object $row, array $totals, ?int $kimiaJurusanId, int $allocatableRoomCount, int $kimiaFixedRoomCount): array
    {
        $classes = (int) ($row->total_classes ?? 0);
        $sks = (int) ($row->total_sks ?? 0);
        $students = (int) ($row->total_students ?? 0);
        $shares = $this->shares($classes, $sks, $students, $totals);
        $weightedScore = ($shares['class_share'] * 0.50) + ($shares['sks_share'] * 0.35) + ($shares['student_share'] * 0.15);
        $rawTargetRooms = $weightedScore * $allocatableRoomCount;
        $fixedRooms = $jurusan->id === $kimiaJurusanId ? $kimiaFixedRoomCount : 0;

        return array_merge($shares, [
            'jurusan_id' => $jurusan->id,
            'nama_jurusan' => $jurusan->nama_jurusan,
            'total_classes' => $classes,
            'total_sks' => $sks,
            'total_students' => $students,
            'avg_students' => round((float) ($row->avg_students ?? 0), 2),
            'large_classes' => (int) ($row->large_classes ?? 0),
            'medium_classes' => (int) ($row->medium_classes ?? 0),
            'weighted_score' => $weightedScore,
            'raw_target_rooms' => $rawTargetRooms,
            'fixed_rooms' => $fixedRooms,
            'variable_demand' => max(0, $rawTargetRooms - $fixedRooms),
        ]);
    }

    private function shares(int $classes, int $sks, int $students, array $totals): array
    {
        return [
            'class_share' => $classes / $totals['classes'],
            'sks_share' => $sks / $totals['sks'],
            'student_share' => $students / $totals['students'],
        ];
    }
}
