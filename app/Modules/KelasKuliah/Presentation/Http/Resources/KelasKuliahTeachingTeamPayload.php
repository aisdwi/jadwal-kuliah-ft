<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahTeachingTeamPayload
{
    public static function from(object $model): array
    {
        $dosens = $model->dosens;
        if (!$dosens || $dosens->isEmpty()) {
            return self::fallback($model->dosen);
        }

        return $dosens->map(fn (object $dosen) => self::withPivot($dosen))->all();
    }

    private static function fallback(?object $dosen): array
    {
        $payload = KelasKuliahDosenPayload::from($dosen);

        return $payload === null ? [] : [array_merge($payload, [
            'pivot' => [
                'preferred_slot_id' => null,
                'is_external' => false,
            ],
        ])];
    }

    private static function withPivot(object $dosen): array
    {
        return array_merge(KelasKuliahDosenPayload::from($dosen), [
            'pivot' => [
                'preferred_slot_id' => $dosen->pivot->preferred_slot_id,
                'is_external' => (bool) $dosen->pivot->is_external,
            ],
        ]);
    }
}
