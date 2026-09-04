<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

final class JadwalSlotPayload
{
    private function __construct(private readonly array $slot) {}

    public static function from(?array $slot): ?array
    {
        return $slot ? (new self($slot))->toArray() : null;
    }

    private function toArray(): array
    {
        return [
            'id' => $this->slot['id'] ?? null,
            'hari_id' => $this->slot['hari_id'] ?? null,
            'waktu_id' => $this->slot['waktu_id'] ?? null,
            'hari' => $this->hariPayload(),
            'waktu' => $this->waktuPayload(),
        ];
    }

    private function hariPayload(): ?array
    {
        $hari = $this->slot['hari'] ?? null;

        return $hari ? [
            'id' => $hari['id'] ?? null,
            'nama_hari' => $hari['nama_hari'] ?? null,
        ] : null;
    }

    private function waktuPayload(): ?array
    {
        $waktu = $this->slot['waktu'] ?? null;

        return $waktu ? [
            'id' => $waktu['id'] ?? null,
            'pukul' => $waktu['pukul'] ?? null,
            'sks' => $waktu['sks'] ?? null,
            'jam_index' => $waktu['jam_index'] ?? null,
        ] : null;
    }
}
