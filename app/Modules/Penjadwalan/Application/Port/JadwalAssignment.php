<?php

namespace App\Modules\Penjadwalan\Application\Port;

/**
 * Value Object: Hasil assignment dari SchedulerPort.
 *
 * Menjadi media komunikasi antara Application layer (Handler) dan
 * Infrastructure layer (GeneticAlgorithmAdapter) tanpa membocorkan
 * detail implementasi GA ke dalam lingkaran Application.
 */
final readonly class JadwalAssignment
{
    public function __construct(
        public int $kelasKuliahId,
        public int $slotId,
        public int $ruanganId,
    ) {}
}
