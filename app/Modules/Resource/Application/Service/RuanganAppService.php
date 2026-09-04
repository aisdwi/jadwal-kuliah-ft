<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Resource\Domain\Repositories\RuanganRepository;
use Illuminate\Support\Arr;

class RuanganAppService
{
    public function __construct(protected RuanganRepository $repo) {}

    public function list(?string $search = null, ?int $jurusanId = null): \Illuminate\Support\Collection
    {
        return $this->repo->all($search, $jurusanId);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Ruangan $id tidak ditemukan");
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = Arr::only($data, ['gedung_id', 'ruangan', 'kapasitas']);
        $ruangan = $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
        if (!$ruangan) {
            return $ruangan;
        }

        $this->syncJurusanAssignments($ruangan, $this->resolveJurusanIds($data, $id));

        return method_exists($ruangan, 'load') ? $ruangan->load(['gedung', 'jurusans']) : $ruangan;
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /**
     * @return list<int>
     */
    private function resolveJurusanIds(array $data, ?int $id): array
    {
        $user = auth()->user();
        $roleName = $user?->role?->role ?? null;
        $ownJurusanId = $user?->jurusan_id ? (int) $user->jurusan_id : null;

        if (RoleName::isJurusanScoped($roleName) && $ownJurusanId !== null) {
            if ($id !== null) {
                $existing = $this->repo->findById($id);
                $existingIds = $existing?->jurusans?->pluck('id')->map(fn ($value) => (int) $value)->all() ?? [];

                return array_values(array_unique([...$existingIds, $ownJurusanId]));
            }

            return [$ownJurusanId];
        }

        return collect($data['jurusan_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param list<int> $jurusanIds
     */
    private function syncJurusanAssignments(object $ruangan, array $jurusanIds): void
    {
        if (!method_exists($ruangan, 'jurusans')) {
            return;
        }

        $ruangan->jurusans()->sync($jurusanIds);
    }
}
