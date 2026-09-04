<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduler;

use App\Modules\Penjadwalan\Application\Port\JadwalAssignment;
use App\Modules\Penjadwalan\Application\Port\SchedulerPort;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\WaktuModel;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Legacy\AlgoritmaGenetika;
use Illuminate\Support\Facades\DB;

/**
 * Infrastructure Adapter: GeneticAlgorithmAdapter
 *
 * Mengimplementasikan SchedulerPort (Application layer interface) dengan
 * membungkus AlgoritmaGenetika legacy (1400+ LoC) tanpa memodifikasi isinya.
 *
 * Pola Adapter (GoF Structural Pattern) memungkinkan:
 * - GA legacy tetap berjalan tanpa perubahan
 * - Application layer tidak tahu detail implementasi GA
 * - Penggantian GA baru (jika ada) hanya perlu ganti class ini
 *
 * Justifikasi teori: Martin Ch. 22 hal. 206 — "The Dependency Rule says that
 * source code dependencies must point only inward." Infrastructure adapter
 * implements Application interface, bukan sebaliknya.
 *
 * Perubahan penting vs. GA legacy:
 * - GA lama menyimpan ke kelas_kuliah.slot_id + kelas_kuliah.ruangan_id (kolom sudah dihapus)
 * - Adapter ini meng-inject resultPersister yang menyimpan ke tabel `jadwal` (Fase 2 schema)
 */
class GeneticAlgorithmAdapter implements SchedulerPort
{
    /**
     * {@inheritdoc}
     */
    public function generateForProdi(int $programStudiId, ?int $semester = null): array
    {
        // ── Load data untuk GA ─────────────────────────────────────────────────

        // Waktu records
        $waktu = WaktuModel::all();

        // Slot records (hari_id × waktu_id)
        $jurusanId = DB::table('program_studi')->where('id', $programStudiId)->value('jurusan_id');
        $slotModel = SlotModel::with(['hari', 'waktu'])
            ->when($jurusanId, function ($query) use ($jurusanId) {
                $query->whereHas('jurusans', fn ($jurusanQuery) => $jurusanQuery->where('jurusan.id', $jurusanId));
            })
            ->get();

        // Ruangan records
        $ruangan = RuanganModel::all();

        // Unscheduled KelasKuliah: yang BELUM ada di tabel jadwal untuk prodi ini
        $scheduledKkIds = DB::table('jadwal')
            ->join('kelas_kuliah', 'jadwal.kelas_kuliah_id', '=', 'kelas_kuliah.id')
            ->join('kelas', 'kelas_kuliah.kelas_id', '=', 'kelas.id')
            ->where('kelas.program_studi_id', $programStudiId)
            ->when($semester, fn ($q) => $q->where('kelas.semester', $semester))
            ->pluck('jadwal.kelas_kuliah_id')
            ->toArray();

        $unscheduledQuery = KelasKuliahModel::with([
            'dosen', 'matakuliah', 'kelas', 'dosens',
        ])->whereHas('kelas', function ($q) use ($programStudiId, $semester) {
            $q->where('program_studi_id', $programStudiId);
            if ($semester !== null) {
                $q->where('semester', $semester);
            }
        });

        if (!empty($scheduledKkIds)) {
            $unscheduledQuery->whereNotIn('id', $scheduledKkIds);
        }

        $kuliah = $unscheduledQuery->get();

        // Manual jadwal: jadwal dengan origin='manual' untuk prodi ini (fixed constraints)
        $manualJadwal = DB::table('jadwal')
            ->join('kelas_kuliah', 'jadwal.kelas_kuliah_id', '=', 'kelas_kuliah.id')
            ->join('kelas', 'kelas_kuliah.kelas_id', '=', 'kelas.id')
            ->where('jadwal.origin', 'manual')
            ->where('kelas.program_studi_id', $programStudiId)
            ->when($semester, fn ($q) => $q->where('kelas.semester', $semester))
            ->select('kelas_kuliah.*', 'jadwal.slot_id', 'jadwal.ruangan_id')
            ->get();

        // Hydrate manual KelasKuliah models with slot_id + ruangan_id from jadwal
        $manualModels = $manualJadwal->map(function ($row) {
            $model = new KelasKuliahModel();
            foreach ((array) $row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            return $model;
        });

        // ── Jalankan GA ────────────────────────────────────────────────────────
        $assignments = [];

        $ga = new AlgoritmaGenetika($waktu, $ruangan, $kuliah, $manualModels, $slotModel);
        $ga->program_studi_id = $programStudiId;

        // Inject resultPersister: simpan ke tabel `jadwal` alih-alih kelas_kuliah
        $ga->resultPersister = function (array $gaAssignments) use (&$assignments) {
            foreach ($gaAssignments as $val) {
                $kelasKuliahId = $val['kuliah'];
                $slotId        = $val['slot'];
                $ruanganId     = $val['ruang'];

                // Upsert: insert atau update jika kelas_kuliah_id sudah ada
                DB::table('jadwal')->upsert(
                    [
                        'kelas_kuliah_id' => $kelasKuliahId,
                        'slot_id'         => $slotId,
                        'ruangan_id'      => $ruanganId,
                        'origin'          => 'generated',
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ],
                    uniqueBy: ['kelas_kuliah_id'],
                    update:   ['slot_id', 'ruangan_id', 'origin', 'updated_at'],
                );

                $assignments[] = new JadwalAssignment(
                    kelasKuliahId: $kelasKuliahId,
                    slotId:        $slotId,
                    ruanganId:     $ruanganId,
                );
            }
        };

        $ga->generate();

        return $assignments;
    }
}
