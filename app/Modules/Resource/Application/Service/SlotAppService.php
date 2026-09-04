<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\Resource\Domain\Repositories\SlotRepository;
use Illuminate\Support\Arr;

class SlotAppService
{
    public function __construct(protected SlotRepository $repo) {}

    public function list(?int $jurusanId = null): \Illuminate\Support\Collection
    {
        return $this->repo->all($jurusanId);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Slot $id tidak ditemukan");
    }

    public function findByHari(int $hariId, ?int $jurusanId = null): \Illuminate\Support\Collection
    {
        return $this->repo->findByHari($hariId, $jurusanId);
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = Arr::only($data, ['hari_id', 'waktu_id']);
        $jurusanId = isset($data['jurusan_id']) ? (int) $data['jurusan_id'] : null;
        $slot = $this->persistSlot($payload, $id, $jurusanId);

        if ($slot && $jurusanId !== null && method_exists($slot, 'jurusans')) {
            $this->syncJurusanSlot($slot, $jurusanId, $id);
        }

        return $slot;
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    private function persistSlot(array $payload, ?int $id = null, ?int $jurusanId = null)
    {
        $hariId = (int) ($payload['hari_id'] ?? 0);
        $waktuId = (int) ($payload['waktu_id'] ?? 0);
        $existing = $this->repo->findByHariAndWaktu($hariId, $waktuId);

        if ($existing && ($id === null || (int) $existing->id !== $id)) {
            return $existing;
        }

        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    private function syncJurusanSlot(object $slot, int $jurusanId, ?int $previousSlotId): void
    {
        $slot->jurusans()->syncWithoutDetaching([$jurusanId]);

        if ($previousSlotId !== null && (int) $slot->id !== $previousSlotId) {
            $previousSlot = $this->repo->findById($previousSlotId);
            $previousSlot?->jurusans()->detach($jurusanId);
        }
    }
}
