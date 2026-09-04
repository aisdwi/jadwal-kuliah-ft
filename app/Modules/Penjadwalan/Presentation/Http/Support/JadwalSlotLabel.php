<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

final class JadwalSlotLabel
{
    public static function from(array $data): string
    {
        return implode(', ', array_values(array_filter([
            trim((string) data_get($data, 'slot.hari.nama_hari', '')),
            trim((string) data_get($data, 'slot.waktu.pukul', '')),
            trim((string) data_get($data, 'ruangan.ruangan', '')),
        ])));
    }
}
