<?php

namespace App\Modules\Shared\Presentation\Http\Resources;

final class SlotReferencePayload
{
    private function __construct(private readonly mixed $slot) {}

    public static function from(mixed $slot): array
    {
        return (new self($slot))->toArray();
    }

    private function toArray(): array
    {
        return [
            'id' => $this->value('id'),
            'hari_id' => $this->value('hari_id'),
            'waktu_id' => $this->value('waktu_id'),
            'jurusan_ids' => $this->jurusanIds(),
            'hari' => $this->relationArray('hari'),
            'waktu' => $this->relationArray('waktu'),
        ];
    }

    private function value(string $key): mixed
    {
        return is_array($this->slot) ? $this->slot[$key] : $this->slot->{$key};
    }

    private function relationArray(string $relation): ?array
    {
        if (is_array($this->slot)) {
            return $this->slot[$relation] ?? null;
        }

        return $this->slot->{$relation}?->toArray();
    }

    private function jurusanIds(): array
    {
        if (is_array($this->slot)) {
            return $this->slot['jurusan_ids'] ?? [];
        }

        if ($this->slot->relationLoaded('jurusans')) {
            return $this->slot->jurusans->pluck('id')->values()->all();
        }

        return [];
    }
}
