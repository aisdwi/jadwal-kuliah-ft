<?php

namespace App\Modules\Penjadwalan\Application\Service;

final readonly class JadwalAssignmentRequest
{
    private function __construct(
        public int $kelasKuliahId,
        public ?int $slotId,
        public ?int $ruanganId,
        private array $data,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            kelasKuliahId: (int) $data['kelas_kuliah_id'],
            slotId: JadwalAssignmentValue::nullableInt($data, 'slot_id'),
            ruanganId: JadwalAssignmentValue::nullableInt($data, 'ruangan_id'),
            data: $data,
        );
    }

    public function isClearAssignment(): bool
    {
        return $this->slotId === null && $this->ruanganId === null;
    }

    public function isCompleteAssignment(): bool
    {
        return $this->slotId !== null && $this->ruanganId !== null;
    }

    public function payload(): array
    {
        $payload = [
            'kelas_kuliah_id' => $this->kelasKuliahId,
            'slot_id' => $this->slotId,
            'ruangan_id' => $this->ruanganId,
        ];

        if (isset($this->data['origin'])) {
            $payload['origin'] = $this->data['origin'];
        }

        return $payload;
    }

}
