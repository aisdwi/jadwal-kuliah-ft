<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Resources;

final class KelasKuliahSlotPayload
{
    public static function from(?object $slot): ?array
    {
        if (!$slot) {
            return null;
        }

        return [
            'id' => $slot->id,
            'hari_id' => $slot->hari_id,
            'waktu_id' => $slot->waktu_id,
            'hari' => $slot->hari ? [
                'id' => $slot->hari->id,
                'nama_hari' => $slot->hari->nama_hari,
            ] : null,
            'waktu' => $slot->waktu ? [
                'id' => $slot->waktu->id,
                'pukul' => $slot->waktu->pukul,
                'sks' => $slot->waktu->sks,
            ] : null,
        ];
    }
}
