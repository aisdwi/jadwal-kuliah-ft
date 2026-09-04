<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\Resource\Domain\Repositories\WaktuRepository;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WaktuAppService
{
    public function __construct(protected WaktuRepository $repo) {}

    public function list(?string $search = null): \Illuminate\Support\Collection
    {
        return $this->repo->all($search);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Waktu $id tidak ditemukan");
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = Arr::only($data, ['pukul', 'sks', 'jam_index']);
        $this->ensureUniqueTime($payload, $id);

        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    private function ensureUniqueTime(array $payload, ?int $ignoreId): void
    {
        $pukul = $this->normalizePukul((string) ($payload['pukul'] ?? ''));

        if ($pukul === '') {
            return;
        }

        foreach ($this->repo->all() as $waktu) {
            if ($ignoreId !== null && (int) $waktu->id === $ignoreId) {
                continue;
            }

            if ($this->normalizePukul((string) $waktu->pukul) === $pukul) {
                throw new HttpException(422, 'Jam kuliah dengan rentang waktu yang sama sudah ada.');
            }
        }
    }

    private function normalizePukul(string $pukul): string
    {
        return preg_replace('/\s+/', '', strtolower(trim($pukul))) ?? '';
    }
}
