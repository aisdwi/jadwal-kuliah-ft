<?php

namespace App\Modules\Penjadwalan\Application\Port;

/**
 * Port: SchedulerPort
 *
 * Interface yang memisahkan Application layer dari implementasi algoritma
 * penjadwalan konkret (Genetic Algorithm, CSP, dsb.).
 *
 * "Port" dalam terminologi Hexagonal Architecture (Cockburn) = kontrak
 * yang didefinisikan Application layer untuk berkomunikasi dengan dunia luar.
 * Implementasi konkret (adapter) ada di Infrastructure layer.
 *
 * Justifikasi: memungkinkan penggantian algoritma GA rekan tim tanpa
 * menyentuh Application/Domain layer — hanya ganti implementasi adapter.
 */
interface SchedulerPort
{
    /**
     * Jalankan penjadwalan otomatis untuk satu program studi.
     *
     * @return JadwalAssignment[]
     */
    public function generateForProdi(int $programStudiId, ?int $semester = null): array;
}
