<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Resources;

use App\Modules\Shared\Presentation\Http\Support\PagedResponseFormatter;
use Illuminate\Database\Eloquent\Model;

class JadwalResource
{
    private const SCALAR_KEYS = [
        'id',
        'kelas_kuliah_id',
        'slot_id',
        'ruangan_id',
        'origin',
    ];
    private const TIMESTAMP_KEYS = [
        'created_at',
        'updated_at',
    ];

    private readonly array $data;

    private function __construct($jadwal)
    {
        $this->data = $jadwal instanceof Model ? $jadwal->toArray() : $jadwal;
    }

    public static function toArray($jadwal): array
    {
        if (!$jadwal) {
            return [];
        }

        return (new self($jadwal))->serialize();
    }

    public static function pagedToArray($result): array
    {
        return PagedResponseFormatter::formatNested(
            $result,
            [self::class, 'toArray'],
            mapUnknownResult: true,
            includePaginationBounds: false,
        );
    }

    private function serialize(): array
    {
        return array_merge(
            $this->scalarValues(),
            [
                'kelas_kuliah' => JadwalKelasKuliahPayload::from($this->value('kelas_kuliah')),
                'slot' => JadwalSlotPayload::from($this->value('slot')),
                'ruangan' => JadwalRuanganPayload::from($this->value('ruangan')),
            ],
            $this->timestampValues(),
        );
    }

    private function scalarValues(): array
    {
        $values = [];

        foreach (self::SCALAR_KEYS as $key) {
            $values[$key] = $this->value($key);
        }

        return $values;
    }

    private function timestampValues(): array
    {
        $values = [];

        foreach (self::TIMESTAMP_KEYS as $key) {
            $values[$key] = $this->value($key);
        }

        return $values;
    }

    private function value(string $key): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }
}
