<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Legacy;

use Illuminate\Support\Facades\DB;

set_time_limit(0);
ini_set('memory_limit', '8192M');

/**
 * Algoritma Genetika untuk Penjadwalan Perkuliahan Otomatis
 * 
 * Adapted from penjadwalan-perkuliahan-kcv project.
 * Supports: team teaching, preserve existing schedules, hard + soft constraints.
 */
class AlgoritmaGenetika
{
    public $num_crommosom = 150;
    public $max_generation = 500;

    public $waktu = [];
    public $waktu_sks = [];
    public $ruang = [];
    public $kuliah = [];
    public $manual = [];      // kelas_kuliah yang sudah dijadwalkan (fixed)
    public $dosen_map = [];   // kelas_kuliah_id => [dosen_id, ...]

    public $generation = 0;
    public $crommosom = [];
    public $fitness = [];

    public $success = false;
    public $debug = false;
    public $isCanceled = false;  // Track if process was canceled

    public $elite_count = 2;
    public $crossover_rate = 85;
    public $mutation_rate = 40;

    public $best_fitness = 0;
    public $best_cromossom = 0;
    public $best_cromossom_printed;
    public $result = [];
    public $resultPersister = null;

    private $stagnant = 0;
    private $last_best = 0;

    public $global_best_fitness = 0;
    public $global_best_cromossom = [];
    public $slot = [];
    public $dosen_preference = [];

    // Fixed constraints from existing schedules
    public $fixed_schedules = [];  // array of ['kuliah' => id, 'ruang' => id, 'slot' => id]

    // Generation log for UI
    public $generation_log = [];

    // Warnings for existing schedule conflicts
    public $warnings = [];

    public $user_id = 'system';
    public $jurusan_id = null;
    public $program_studi_id = null;
    public $progress_started_at = null;
    public $progress_scope_label = 'Semua Data';
    public $jurusan_ruang_map = [];
    public $mku_ruang_ids = [];

    private $fixed_room_slot_usage = [];
    private $fixed_slot_usage = [];
    private $fixed_dosen_slot_usage = [];
    private $fixed_kelas_slot_usage = [];
    private $fixed_angkatan_slot_usage = [];

    

    /* ================= CONSTRUCTOR ================= */

    /**
     * @param Collection $waktu   Waktu records
     * @param Collection $ruang   Ruangan records  
     * @param Collection $kuliah  Unscheduled KelasKuliah with dosens + matakuliah + kelas
     * @param Collection $manual  Already scheduled KelasKuliah (treated as fixed constraints)
     * @param Collection $slot    Slot records (hari_id + waktu_id)
     */
    public function __construct($waktu, $ruang, $kuliah, $manual, $slot)
    {
        foreach ($waktu as $row) {
            $this->waktu[$row->id] = $row;
            if (isset($row->sks)) {
                $this->waktu_sks[$row->sks][$row->id] = $row->id;
            }
        }

        foreach ($slot as $row) {
            $this->slot[$row->id] = $row;
        }

        foreach ($ruang as $row) {
            $this->ruang[$row->id] = $row;
        }

        // Unscheduled classes to be assigned by GA
        foreach ($kuliah as $row) {
            $this->kuliah[$row->id] = $row;

            // Team teaching: get all dosen IDs from pivot table
            if ($row->relationLoaded('dosens') && $row->dosens->count() > 0) {
                $this->dosen_map[$row->id] = $row->dosens->pluck('id')->toArray();

                // Dosen preferences for slots
                foreach ($row->dosens as $d) {
                    if (isset($d->pivot) && $d->pivot->preferred_slot_id) {
                        $this->dosen_preference[$row->id][$d->id] = $d->pivot->preferred_slot_id;
                    }
                }
            } else {
                // Fallback: use dosen_id directly (backward compat)
                $this->dosen_map[$row->id] = $row->dosen_id ? [$row->dosen_id] : [];
            }
        }

        // Already scheduled classes — treated as fixed constraints
        foreach ($manual as $row) {
            $this->manual[$row->id] = $row;

            // Build fixed schedules array
            $this->fixed_schedules[] = [
                'kuliah' => $row->id,
                'ruang'  => $row->ruangan_id,
                'slot'   => $row->slot_id,
            ];

            // Also map their dosens for clash detection
            if ($row->relationLoaded('dosens') && $row->dosens->count() > 0) {
                $this->dosen_map[$row->id] = $row->dosens->pluck('id')->toArray();
            } else {
                $this->dosen_map[$row->id] = $row->dosen_id ? [$row->dosen_id] : [];
            }
        }

        // Check for existing schedule conflicts
        $this->checkExistingConflicts();
        $this->buildFixedConstraintIndexes();
    }

    /* ================= CHECK EXISTING CONFLICTS ================= */

    public function checkExistingConflicts()
    {
        if (count($this->fixed_schedules) < 2) return;

        // Check room clashes in fixed schedules
        $roomSlotMap = [];
        $dosenSlotMap = [];

        foreach ($this->fixed_schedules as $fs) {
            $roomKey = $fs['ruang'] . '_' . $fs['slot'];
            if (isset($roomSlotMap[$roomKey])) {
                $kk1 = $this->manual[$roomSlotMap[$roomKey]] ?? null;
                $kk2 = $this->manual[$fs['kuliah']] ?? null;
                $name1 = $kk1 && $kk1->matakuliah ? $kk1->matakuliah->nama_mk : "KK#{$roomSlotMap[$roomKey]}";
                $name2 = $kk2 && $kk2->matakuliah ? $kk2->matakuliah->nama_mk : "KK#{$fs['kuliah']}";
                $this->warnings[] = "⚠️ Konflik ruangan pada jadwal existing: {$name1} & {$name2} di ruangan & slot yang sama";
            }
            $roomSlotMap[$roomKey] = $fs['kuliah'];

            // Check dosen clashes
            if (isset($this->dosen_map[$fs['kuliah']])) {
                foreach ($this->dosen_map[$fs['kuliah']] as $dosenId) {
                    $dosenKey = $dosenId . '_' . $fs['slot'];
                    if (isset($dosenSlotMap[$dosenKey])) {
                        $kk1 = $this->manual[$dosenSlotMap[$dosenKey]] ?? null;
                        $kk2 = $this->manual[$fs['kuliah']] ?? null;
                        $name1 = $kk1 && $kk1->matakuliah ? $kk1->matakuliah->nama_mk : "KK#{$dosenSlotMap[$dosenKey]}";
                        $name2 = $kk2 && $kk2->matakuliah ? $kk2->matakuliah->nama_mk : "KK#{$fs['kuliah']}";
                        $this->warnings[] = "⚠️ Konflik dosen pada jadwal existing: {$name1} & {$name2} dengan dosen yang sama di slot yang sama";
                    }
                    $dosenSlotMap[$dosenKey] = $fs['kuliah'];
                }
            }
        }
    }

    public function buildFixedConstraintIndexes()
    {
        $this->fixed_room_slot_usage = [];
        $this->fixed_slot_usage = [];
        $this->fixed_dosen_slot_usage = [];
        $this->fixed_kelas_slot_usage = [];
        $this->fixed_angkatan_slot_usage = [];

        foreach ($this->fixed_schedules as $assignment) {
            $this->applyAssignmentUsage(
                $this->fixed_slot_usage,
                $this->fixed_room_slot_usage,
                $this->fixed_dosen_slot_usage,
                $this->fixed_kelas_slot_usage,
                $this->fixed_angkatan_slot_usage,
                $assignment['kuliah'],
                $assignment['slot'],
                $assignment['ruang']
            );
        }
    }

    /* ================= GENERATE ================= */

    public function generate()
    {
        if (empty($this->kuliah)) {
            $this->result = [
                'best_fitness' => 1,
                'generation' => 0,
                'best_cromossom' => 0,
                'best_cromossom_printed' => '<p>Tidak ada kelas yang perlu dijadwalkan.</p>',
                'rur' => $this->hitungRUR(),
                'clashes' => [],
                'warnings' => $this->warnings,
                'generation_log' => [],
            ];
            return;
        }

        $startTime = microtime(true);

        // Log: Initialization start
        $this->addLog('info', "Inisialisasi {$this->num_crommosom} kromosom untuk " . count($this->kuliah) . " kelas kuliah...");
        $this->updateProgressCache();

        $this->generate_crommosom();

        // Log: Initialization complete
        $initTime = round(microtime(true) - $startTime, 2);
        $this->addLog('success', "Inisialisasi selesai dalam {$initTime}s. Memulai evolusi (max {$this->max_generation} generasi)...");
        $this->updateProgressCache();

        $resetCount = 0;

        while ($this->generation < $this->max_generation) {
            if ($this->isCancellationRequested()) {
                $this->addLog('warning', "Proses dibatalkan oleh pengguna sebelum memulai generasi berikutnya.");
                $this->updateProgressCache();
                return;
            }

            $this->generation++;
            $this->best_fitness = 0;
            $this->best_cromossom = 0;

            $this->calculate_all_fitness();

            // Check if user requested cancellation during fitness calculation
            if ($this->isCanceled) {
                $this->addLog('warning', "Proses dibatalkan oleh pengguna pada Gen {$this->generation}.");
                $this->updateProgressCache();
                return;
            }

            // Check if user requested cancellation after fitness calculation
            if ($this->isCancellationRequested()) {
                $this->addLog('warning', "Proses dibatalkan oleh pengguna pada Gen {$this->generation}.");
                $this->updateProgressCache();
                return;
            }

            // Determine improvement indicator
            $prevBest = $this->last_best;
            $improved = $this->best_fitness > $prevBest;

            // Compute clashes for logging
            $best = $this->crommosom[$this->best_cromossom];
            $combined = array_merge($this->fixed_schedules, $best);
            $cd = count($this->get_clash_dosen($combined));
            $cr = count($this->get_clash_ruang($combined));
            $ck = count($this->get_clash_kelas($combined));
            $ca = count($this->get_clash_angkatan($combined));
            $totalClash = $cd + $cr + $ck + $ca;
            $softBreakdown = $this->getSoftPenaltyBreakdown($best);

            // Build generation log entry
            $indicator = '↓';
            if ($improved) {
                $indicator = '↑';
            } elseif ($this->best_fitness == $prevBest) {
                $indicator = '→';
            }
            $elapsed = round(microtime(true) - $startTime, 1);

            $genLogEntry = [
                'type' => 'generation',
                'gen' => $this->generation,
                'fitness' => round($this->best_fitness, 6),
                'global_best' => round($this->global_best_fitness, 6),
                'clash_dosen' => $cd,
                'clash_ruang' => $cr,
                'clash_kelas' => $ck,
                'clash_angkatan' => $ca,
                'total_clash' => $totalClash,
                'hard_total' => $totalClash,
                'hard_dosen' => $cd,
                'hard_ruang' => $cr,
                'hard_kelas' => $ck,
                'hard_angkatan' => $ca,
                'soft_total' => $softBreakdown['total'],
                'soft_pw' => $softBreakdown['pw'],
                'soft_mk' => $softBreakdown['mk'],
                'soft_jw' => $softBreakdown['jw'],
                'stagnant' => $this->stagnant,
                'indicator' => $indicator,
                'elapsed' => $elapsed,
                'timestamp' => now()->format('H:i:s'),
            ];
            $this->generation_log[] = $genLogEntry;

            if ($this->last_best !== null && $this->best_fitness <= $this->last_best) {
                $this->stagnant++;
            } else {
                $this->stagnant = 0;
                $this->last_best = $this->best_fitness;
            }

            $best = $this->crommosom[$this->best_cromossom];
            $unassigned = $this->count_unassigned($best);

            if ($this->best_fitness > $this->global_best_fitness && $unassigned == 0) {
                $this->global_best_fitness = $this->best_fitness;
                $this->global_best_cromossom = $best;
                $this->addLog('success', "✓ Best fitness baru: " . round($this->global_best_fitness, 6) . " (Gen {$this->generation})");
            }

            // Update cache every generation for real-time UI
            $this->updateProgressCache();

            if ($this->stagnant > 60) {
                $resetCount++;
                $this->addLog('warning', "⚠ Stagnasi terdeteksi ({$this->stagnant} gen tanpa perbaikan). Reset populasi #{$resetCount}...");

                $this->generate_crommosom();

                $this->stagnant = 0;

                $this->last_best = 0;

                $this->addLog('info', "Populasi baru dibangkitkan. Melanjutkan evolusi...");
                $this->updateProgressCache();
                continue;
            }

            $best = $this->crommosom[$this->best_cromossom];
            $unassigned = $this->count_unassigned($best);
            $global_unassigned = $this->count_unassigned($this->global_best_cromossom);

            if ($this->global_best_fitness >= 1.0 && $global_unassigned == 0) {
                $this->addLog('success', "★ Fitness sempurna (1.0) tercapai pada Gen {$this->generation}! Mengoptimasi soft constraint...");
                $this->updateProgressCache();
                break;
            }

            $elite_indexes = $this->get_elite();
            $elite = [];

            foreach ($elite_indexes as $i)
                $elite[$i] = $this->crommosom[$i];

            $this->selection();
            $this->crossover();
            $this->mutation();

            if ($this->isCancellationRequested()) {
                $this->addLog('warning', "Proses dibatalkan oleh pengguna setelah operasi evolusi Gen {$this->generation}.");
                $this->updateProgressCache();
                return;
            }

            for ($i = 0; $i < 5; $i++) {
                $idx = array_rand($this->crommosom);
                if (!isset($elite[$idx])) {
                    $this->crommosom[$idx] = $this->get_rand_crommosom();
                }
            }

            foreach ($elite as $i => $cro)
                $this->crommosom[$i] = $cro;
        }

        if (empty($this->global_best_cromossom) && isset($this->crommosom[$this->best_cromossom]) && !empty($this->crommosom[$this->best_cromossom])) {
            logger()->info('legacy_ga_fallback_to_best_cromossom', [
                'best_cromossom_index' => $this->best_cromossom,
                'best_cromossom_size' => count($this->crommosom[$this->best_cromossom]),
                'best_fitness' => $this->best_fitness,
            ]);
            $this->global_best_cromossom = $this->crommosom[$this->best_cromossom];
            $this->global_best_fitness = $this->best_fitness;
        }

        $this->crommosom[0] = $this->global_best_cromossom;
        $this->best_cromossom = 0;
        $this->best_fitness = $this->global_best_fitness;

        // Recalculate so all_clash is synced
        $this->calculate_fitness(0);

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->addLog('info', "Evolusi selesai dalam {$totalTime}s. Menyimpan hasil ke database...");
        $this->updateProgressCache();

        if ($this->isCancellationRequested()) {
            $this->addLog('warning', "Proses dibatalkan oleh pengguna sebelum penyimpanan hasil.");
            $this->updateProgressCache();
            return;
        }

        // 🔍 DEBUG CEK SLOT SEBELUM DISIMPAN
        foreach ($this->crommosom[$this->best_cromossom] as $g) {
            if (!isset($this->slot[$g['slot']])) {
                dd("ADA SLOT NULL DI GA", $g);
            }
        }

        $final_unassigned = $this->count_unassigned($this->global_best_cromossom);

        if ($final_unassigned > 0) {
            logger()->info('DEBUG GA', [
                'final_unassigned' => $final_unassigned
            ]);
        }
        $this->save_result();

        $this->addLog('success', "✓ Hasil tersimpan. Fitness akhir: " . round($this->best_fitness, 6));
        $this->updateProgressCache();

        $this->best_cromossom_printed = $this->print_cros($this->crommosom[0], 0);

        $this->result = [
            'best_fitness' => $this->best_fitness,
            'generation' => $this->generation,
            'best_cromossom' => 0,
            'best_cromossom_printed' => $this->best_cromossom_printed,
            'rur' => $this->hitungRUR(),
            'clashes' => $this->getClashSummary(),
            'warnings' => $this->warnings,
            'generation_log' => $this->generation_log,
        ];
    }

    /* ================= CANCELLATION CHECK ================= */

    /**
     * Check if user requested cancellation.
     * This also removes the flag from cache if found.
     * @return bool True if cancellation was requested
     */
    private function isCancellationRequested()
    {
        $cacheKey = 'ga_cancel_' . $this->user_id;
        $progressCache = \Illuminate\Support\Facades\Cache::store('database');

        $isCanceled = $progressCache->get($cacheKey, false);

        if ($isCanceled) {
            $this->isCanceled = true;
        }

        return (bool) $isCanceled;
    }

    /* ================= LOGGING HELPERS ================= */

    public function addLog($type, $message)
    {
        $this->generation_log[] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->format('H:i:s'),
        ];
    }

    public function updateProgressCache()
    {
        try {
            \Illuminate\Support\Facades\Cache::store('database')->put('ga_progress_' . $this->user_id, [
                'status' => $this->isCanceled ? 'canceled' : 'processing',
                'generation' => $this->generation,
                'max_generation' => $this->max_generation,
                'best_fitness' => $this->global_best_fitness,
                'logs' => array_slice($this->generation_log, -50), // Keep last 50 logs
                'result' => null,
                'started_at' => $this->progress_started_at ?: now()->toISOString(),
                'scope_label' => $this->progressScopeLabel(),
            ], 3600);
        } catch (\Exception $e) {
            report($e);
        }
    }

    private function progressScopeLabel(): string
    {
        if ($this->progress_scope_label) {
            return $this->progress_scope_label;
        }

        if ($this->program_studi_id) {
            return 'Program Studi';
        }

        if ($this->jurusan_id) {
            return 'Jurusan';
        }

        return 'Semua Data';
    }

    /* ================= FITNESS ================= */

    public function calculate_all_fitness()
    {
        foreach ($this->crommosom as $key => $val) {
            if (\Illuminate\Support\Facades\Cache::store('database')->has('ga_cancel_' . $this->user_id)) {
                break;
            }
            // Check for cancellation during fitness calculation
            if ($this->isCancellationRequested()) {
                $this->addLog('warning', "Proses dibatalkan oleh pengguna selama perhitungan fitness.");
                return; // Early exit
            }
            $this->calculate_fitness($key);
        }
    }

    public function calculate_fitness($key)
    {
        $cro = $this->crommosom[$key];

        // Combine with fixed schedules for clash detection
        $combined = array_merge($this->fixed_schedules, $cro);

        // ================= HARD =================
        $clash_d = $this->get_clash_dosen($combined);
        $clash_r = $this->get_clash_ruang($combined);
        $clash_k = $this->get_clash_kelas($combined);
        $clash_a = $this->get_clash_angkatan($combined);

        // Only count clashes in the GA-generated portion (not the fixed ones)
        $fixedCount = count($this->fixed_schedules);
        $filterGAOnly = function ($arr) use ($fixedCount) {
            $result = [];
            foreach ($arr as $idx => $val) {
                if ($idx >= $fixedCount) {
                    $result[$idx - $fixedCount] = $val - $fixedCount;
                }
            }
            return $result;
        };

        $ga_clash_d = $filterGAOnly($clash_d);
        $ga_clash_r = $filterGAOnly($clash_r);
        $ga_clash_k = $filterGAOnly($clash_k);
        $ga_clash_a = $filterGAOnly($clash_a);

        $all_clash = array_unique(array_merge(
            array_keys($ga_clash_d),
            array_keys($ga_clash_r),
            array_keys($ga_clash_k),
            array_keys($ga_clash_a)
        ));

        $hard_clash =
            count($ga_clash_d) * 5 +
            count($ga_clash_r) * 5 +
            count($ga_clash_k) * 3 +
            count($ga_clash_a);

        // ================= SOFT =================
        $fixed_teaching_penalty = $this->get_dosen_teaching_penalty($this->fixed_schedules);
        $combined_teaching_penalty = $this->get_dosen_teaching_penalty($combined);

        $teaching_penalty = max(0, $combined_teaching_penalty - $fixed_teaching_penalty);

        $soft_penalty =
            $teaching_penalty +
            $this->get_preference_penalty($cro);
        // ================= FITNESS FINAL =================
        if ($hard_clash > 0) {
            $nilai = 1 / (1 + $hard_clash);
        } else {
            $this->success = true;

            $max_soft = 100;
            $soft_score = 1 - min($soft_penalty / $max_soft, 1);
            $nilai = 0.5 + (0.5 * $soft_score);

            $unassigned = $this->count_unassigned($cro);

            if ($unassigned > 0) {
                $nilai = min($nilai, 0.99);
            }
        }

        // ================= BEST =================
        if ($nilai > $this->best_fitness) {
            $this->best_fitness = $nilai;
            $this->best_cromossom = $key;
        }

        $this->fitness[$key] = [
            'nilai' => $nilai,
            'all_clash' => $all_clash,
            'hard_clash' => $hard_clash,
            'soft_penalty' => $soft_penalty,
        ];
    }

    /* ================= CLASH FUNCTIONS ================= */

    public function get_clash_ruang($crom)
    {
        $group = [];
        $arr = [];

        foreach ($crom as $i => $g) {
            if ($this->isUnassignedGene($g)) continue;

            $key = $g['ruang'] . '_' . $g['slot'];
            $group[$key][] = $i;
        }

        foreach ($group as $list) {
            if (count($list) > 1) {
                foreach ($list as $idx)
                    $arr[$idx] = $idx;
            }
        }

        return $arr;
    }

    public function get_clash_kelas($crom)
    {
        $map = [];
        $arr = [];

        foreach ($crom as $i => $g) {
            if ($this->isUnassignedGene($g)) continue;

            $kuliahId = $g['kuliah'];
            // Get kelas_id from either GA-assigned or fixed
            $kuliahObj = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);
            if (!$kuliahObj) continue;

            $kelas_id = $kuliahObj->kelas_id;
            $key = $kelas_id . '_' . $g['slot'];

            if (isset($map[$key])) {
                $arr[$i] = $i;
                $arr[$map[$key]] = $map[$key];
            } else {
                $map[$key] = $i;
            }
        }

        return $arr;
    }

    public function get_clash_angkatan($crom)
    {
        $map = [];
        $arr = [];

        foreach ($crom as $i => $g) {
            if ($this->isUnassignedGene($g)) continue;

            $kuliahId = $g['kuliah'];
            $kuliahObj = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);
            if (!$kuliahObj || !$kuliahObj->kelas) continue;

            $kelas = $kuliahObj->kelas;
            $semester = $kelas->semester;
            $prodi = $kelas->program_studi_id;

            $key = $semester . '_' . $prodi . '_' . $g['slot'];

            if (isset($map[$key])) {
                $arr[$i] = $i;
                $arr[$map[$key]] = $map[$key];
            } else {
                $map[$key] = $i;
            }
        }

        return $arr;
    }

    public function get_clash_dosen($crom)
    {
        $map = [];
        $arr = [];

        foreach ($crom as $i => $g) {
            if ($this->isUnassignedGene($g)) continue;

            $kuliahId = $g['kuliah'];
            $dosens = $this->dosen_map[$kuliahId] ?? [];

            foreach ($dosens as $dosen) {
                $key = $dosen . '_' . $g['slot'];

                if (isset($map[$key])) {
                    $arr[$i] = $i;
                    $arr[$map[$key]] = $map[$key];
                } else {
                    $map[$key] = $i;
                }
            }
        }

        return $arr;
    }

    public function get_preference_penalty($cro)
    {
        $penalty = 0;

        foreach ($cro as $g) {
            if ($this->isUnassignedGene($g)) continue;

            $kuliah = $g['kuliah'];
            $slot = $g['slot'];

            if (!isset($this->dosen_preference[$kuliah])) continue;

            foreach ($this->dosen_preference[$kuliah] as $dosen => $pref_slot) {
                if ($slot != $pref_slot) {
                    $penalty += 1;
                }
            }
        }

        return $penalty;
    }

    public function get_dosen_teaching_penalty($cro)
    {
        $map = [];
        $penalty = 0;

        foreach ($cro as $g) {
            $kuliahId = $g['kuliah'];
            $slotObj = $this->slot[$g['slot']] ?? null;
            if (!$slotObj) continue;

            $hari = $slotObj->hari_id;
            $waktuObj = $this->waktu[$slotObj->waktu_id] ?? null;
            $jam = $waktuObj ? ($waktuObj->jam_index ?? $slotObj->waktu_id) : $slotObj->waktu_id;

            $dosens = $this->dosen_map[$kuliahId] ?? [];
            foreach ($dosens as $dosen) {
                $map[$dosen][$hari][] = $jam;
            }
        }

        foreach ($map as $dosen => $hari_list) {
            foreach ($hari_list as $hari => $jam_list) {
                sort($jam_list);

                // RULE 1: max 2 classes per day
                if (count($jam_list) > 2) {
                    $penalty += (count($jam_list) - 2) * 2;
                }

                // RULE 2: must have gap (not consecutive)
                for ($i = 0; $i < count($jam_list) - 1; $i++) {
                    if (abs($jam_list[$i] - $jam_list[$i + 1]) == 1) {
                        $penalty += 1;
                    }
                }
            }
        }

        return $penalty;
    }

    private function getSoftPenaltyBreakdown($cro)
    {
        $pw = $this->get_preference_penalty($cro);
        $teachingBreakdown = $this->getDosenTeachingPenaltyBreakdown($cro);

        return [
            // PW: Preferensi Waktu dosen
            'pw' => $pw,
            // MK: Maksimal kelas per hari (lebih dari 2)
            'mk' => $teachingBreakdown['mk'],
            // JW: Jeda Waktu (mengajar beruntun tanpa jeda)
            'jw' => $teachingBreakdown['jw'],
            'total' => $pw + $teachingBreakdown['mk'] + $teachingBreakdown['jw'],
        ];
    }

    private function getDosenTeachingPenaltyBreakdown($cro)
    {
        $map = [];
        $mkPenalty = 0;
        $jwPenalty = 0;

        foreach ($cro as $g) {
            $kuliahId = $g['kuliah'];
            $slotObj = $this->slot[$g['slot']] ?? null;
            if (!$slotObj) continue;

            $hari = $slotObj->hari_id;
            $waktuObj = $this->waktu[$slotObj->waktu_id] ?? null;
            $jam = $waktuObj ? ($waktuObj->jam_index ?? $slotObj->waktu_id) : $slotObj->waktu_id;

            $dosens = $this->dosen_map[$kuliahId] ?? [];
            foreach ($dosens as $dosen) {
                $map[$dosen][$hari][] = $jam;
            }
        }

        foreach ($map as $hariList) {
            foreach ($hariList as $jamList) {
                sort($jamList);

                if (count($jamList) > 2) {
                    $mkPenalty += (count($jamList) - 2) * 2;
                }

                for ($i = 0; $i < count($jamList) - 1; $i++) {
                    if (abs($jamList[$i] - $jamList[$i + 1]) == 1) {
                        $jwPenalty += 1;
                    }
                }
            }
        }

        return [
            'mk' => $mkPenalty,
            'jw' => $jwPenalty,
            'total' => $mkPenalty + $jwPenalty,
        ];
    }

    public function getKuliahSks($kuliahId)
    {
        $kuliah = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);

        if (!$kuliah || !$kuliah->matakuliah || !isset($kuliah->matakuliah->sks)) {
            return null;
        }

        return (int) $kuliah->matakuliah->sks;
    }

    public function getSlotSks($slotId)
    {
        $slot = $this->slot[$slotId] ?? null;

        if (!$slot) {
            return null;
        }

        $waktu = $this->waktu[$slot->waktu_id] ?? null;

        if (!$waktu || !isset($waktu->sks)) {
            return null;
        }

        return (int) $waktu->sks;
    }

    public function isSlotSesuaiSks($kuliahId, $slotId)
    {
        $sksKuliah = $this->getKuliahSks($kuliahId);
        $sksSlot = $this->getSlotSks($slotId);

        if ($sksKuliah === null || $sksSlot === null) {
            return false;
        }

        return $sksKuliah === $sksSlot;
    }

    public function getSlotSesuaiSks($kuliahId)
    {
        $sksKuliah = $this->getKuliahSks($kuliahId);

        if ($sksKuliah === null) {
            return [];
        }

        $slotValid = [];

        foreach ($this->slot as $slotId => $slot) {
            if ($this->getSlotSks($slotId) === $sksKuliah) {
                $slotValid[] = $slotId;
            }
        }

        return $slotValid;
    }

    public function total_clash($cro)
    {
        // Include fixed schedules for clash checking
        $combined = array_merge($this->fixed_schedules, $cro);

        $room_usage = [];

        foreach ($combined as $g) {
            if ($this->isUnassignedGene($g)) continue;

            $room_usage[$g['ruang']] = ($room_usage[$g['ruang']] ?? 0) + 1;
        }

        $penalty = 0;

        foreach ($room_usage as $count) {
            if ($count > 5) {
                $penalty += ($count - 5);
            }
        }

        return
            count($this->get_clash_dosen($combined)) * 3 +
            count($this->get_clash_ruang($combined)) * 2 +
            count($this->get_clash_kelas($combined)) * 2 +
            count($this->get_clash_angkatan($combined)) +
            $penalty;
    }

    private function isUnassignedGene(array $gene): bool
    {
        $slot = $gene['slot'] ?? null;
        $ruang = $gene['ruang'] ?? null;

        return empty($slot)
            || empty($ruang)
            || !array_key_exists($slot, $this->slot)
            || !array_key_exists($ruang, $this->ruang);
    }

    /* ================= GENERATOR ================= */

    public function generate_crommosom()
    {
        $this->crommosom = [];
        $reportEvery = max(1, (int) ceil($this->num_crommosom / 5));

        for ($i = 0; $i < $this->num_crommosom; $i++) {
            if ($this->isCancellationRequested()) {
                return;
            }
            $this->crommosom[$i] = $this->get_rand_crommosom();

            if ((($i + 1) % $reportEvery) === 0 || ($i + 1) === $this->num_crommosom) {
                $percent = (int) round((($i + 1) / max(1, $this->num_crommosom)) * 100);
                $this->addLog('info', "Inisialisasi populasi: " . ($i + 1) . "/{$this->num_crommosom} kromosom ({$percent}%).");
                $this->updateProgressCache();
            }
        }
    }

    public function get_rand_crommosom()
    {
        $result = [];
        $slot_usage = $this->fixed_slot_usage;
        $room_slot_usage = $this->fixed_room_slot_usage;
        $dosen_slot_usage = $this->fixed_dosen_slot_usage;
        $kelas_slot_usage = $this->fixed_kelas_slot_usage;
        $angkatan_slot_usage = $this->fixed_angkatan_slot_usage;
        $slotIds = array_keys($this->slot);
        $roomIds = array_keys($this->ruang);
        $maxRoomsPerSlot = count($roomIds);
        $noSlotExamples = [];

        logger()->info('legacy_ga_rand_cromossom_start', [
            'total_kuliah' => count($this->kuliah),
            'slot_count' => count($slotIds),
            'room_count' => $maxRoomsPerSlot,
            'generation' => $this->generation,
        ]);

        if ($maxRoomsPerSlot === 0 || empty($slotIds)) {
            logger()->warning('legacy_ga_rand_cromossom_no_slots_or_rooms', [
                'slot_count' => count($slotIds),
                'room_count' => $maxRoomsPerSlot,
            ]);
            return [];
        }

        foreach ($this->kuliah as $key => $kuliah) {
            if ($this->isCancellationRequested()) {
                return $result;
            }

            $fail_reason = [
                'slot_full' => 0,
                'room_used' => 0,
                'capacity' => 0,
            ];

            $slots = $this->getSlotSesuaiSks($key);

            if (empty($slots)) {
                if (count($noSlotExamples) < 10) {
                    $noSlotExamples[] = [
                        'kuliah_id' => $key,
                        'matakuliah_id' => $kuliah->matakuliah->id ?? null,
                        'matakuliah_sks' => $kuliah->matakuliah->sks ?? null,
                        'jumlah_mahasiswa' => $kuliah->jumlah_mahasiswa ?? null,
                    ];
                }
                continue;
            }
            shuffle($slots);

            $best_slot = null;
            $best_ruang = null;
            $best_score = PHP_INT_MAX;

            foreach ($slots as $slot) {
                // Limit slot capacity

                if (($slot_usage[$slot] ?? 0) >= $maxRoomsPerSlot) {
                    $fail_reason['slot_full']++;
                    continue;
                }

                // Get allowed rooms for this class based on Jurusan or MKU
                $kodeMk = isset($this->kuliah[$key]->matakuliah->kode_mk) ? $this->kuliah[$key]->matakuliah->kode_mk : '';

                if (str_starts_with($kodeMk, 'U')) {
                    $allowedRooms = !empty($this->mku_ruang_ids) ? $this->mku_ruang_ids : $roomIds;
                    $candidateRooms = array_intersect($roomIds, $allowedRooms);
                } else {
                    $jurusanId = isset($this->kuliah[$key]->matakuliah->jurusan_id)
                        ? $this->kuliah[$key]->matakuliah->jurusan_id : null;
                    $allowedRooms = ($jurusanId && !empty($this->jurusan_ruang_map[$jurusanId]))
                        ? $this->jurusan_ruang_map[$jurusanId] : $roomIds;

                    $candidateRooms = array_intersect($roomIds, $allowedRooms);
                }

                if (empty($candidateRooms)) $candidateRooms = $roomIds;

                $candidateRooms = array_values($candidateRooms);
                shuffle($candidateRooms);

                foreach ($candidateRooms as $rid) {
                    $ruang = $this->ruang[$rid];

                    if (isset($room_slot_usage[$slot][$rid])) {
                        $fail_reason['room_used']++;
                        continue;
                    }

                    if ($ruang->kapasitas < $this->kuliah[$key]->jumlah_mahasiswa) {
                        $fail_reason['capacity']++;
                        continue;
                    }

                    $score = $this->scoreCandidateAssignment(
                        $key,
                        $slot,
                        $rid,
                        $slot_usage,
                        $dosen_slot_usage,
                        $kelas_slot_usage,
                        $angkatan_slot_usage
                    );

                    if ($score < $best_score || ($score === $best_score && mt_rand(0, 1) === 1)) {
                        $best_score = $score;
                        $best_slot = $slot;
                        $best_ruang = $rid;

                        if ($score === 0) {
                            break;
                        }
                    }
                }

                if ($best_score === 0) {
                    break;
                }
            }

            // =====================================================
            // JIKA TIDAK MENEMUKAN SOLUSI IDEAL
            // =====================================================

            if ($best_slot === null || $best_ruang === null) {

                $lowestPenalty = PHP_INT_MAX;

                $validSlotIds = $this->getSlotSesuaiSks($key);

                foreach ($validSlotIds as $slotId) {

                    foreach ($roomIds as $roomId) {

                        $invalid = false;

                        // RUANG
                        if (isset($room_slot_usage[$slotId][$roomId])) {
                            $invalid = true;
                        }

                        // KAPASITAS
                        if (
                            ($this->ruang[$roomId]->kapasitas ?? 0)
                            < ($kuliah->jumlah_mahasiswa ?? 0)
                        ) {
                            $invalid = true;
                        }

                        // DOSEN
                        foreach ($this->dosen_map[$key] ?? [] as $dosenId) {

                            if (isset($dosen_slot_usage[$slotId][$dosenId])) {
                                $invalid = true;
                                break;
                            }
                        }

                        // KELAS
                        if (
                            !$invalid &&
                            !empty($kuliah->kelas_id) &&
                            isset($kelas_slot_usage[$slotId][$kuliah->kelas_id])
                        ) {
                            $invalid = true;
                        }

                        if ($invalid) {
                            continue;
                        }

                        // HANYA ANGKATAN YANG BOLEH MELANGGAR
                        $penalty = 0;

                        $angkatanKey = $this->getAcademicGroupKey($key);

                        if (
                            $angkatanKey !== null &&
                            isset($angkatan_slot_usage[$slotId][$angkatanKey])
                        ) {
                            $penalty += 20;
                        }

                        if ($penalty < $lowestPenalty) {

                            $lowestPenalty = $penalty;

                            $best_slot = $slotId;
                            $best_ruang = $roomId;
                        }
                    }
                }
            }

            if ($best_slot === null || $best_ruang === null) {

                $result[] = [
                    'kuliah' => $key,
                    'ruang' => null,
                    'slot' => null
                ];

                continue;
            }

            $result[] = [
                'kuliah' => $key,
                'ruang' => $best_ruang,
                'slot' => $best_slot
            ];

            $this->applyAssignmentUsage(
                $slot_usage,
                $room_slot_usage,
                $dosen_slot_usage,
                $kelas_slot_usage,
                $angkatan_slot_usage,
                $key,
                $best_slot,
                $best_ruang
            );
        }

        return $result;
    }

    public function scoreCandidateAssignment($kuliahId, $slotId, $roomId, $slotUsage, $dosenSlotUsage, $kelasSlotUsage, $angkatanSlotUsage)
    {
        $score = max(0, ($slotUsage[$slotId] ?? 0) - max(1, count($this->ruang) - 2));

        foreach ($this->dosen_map[$kuliahId] ?? [] as $dosenId) {
            $score += (($dosenSlotUsage[$slotId][$dosenId] ?? 0) * 5);

            if (isset($this->dosen_preference[$kuliahId][$dosenId]) && (int) $this->dosen_preference[$kuliahId][$dosenId] !== (int) $slotId) {
                $score += 1;
            }
        }

        $kuliahObj = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);

        if (!$kuliahObj) {
            return $score;
        }

        if ($kuliahObj->kelas_id) {
            $score += (($kelasSlotUsage[$slotId][$kuliahObj->kelas_id] ?? 0) * 3);
        }

        $angkatanKey = $this->getAcademicGroupKey($kuliahId);
        if ($angkatanKey !== null) {
            $score += ($angkatanSlotUsage[$slotId][$angkatanKey] ?? 0);
        }

        if (isset($this->ruang[$roomId])) {
            $capacityGap = max(0, (int) $this->ruang[$roomId]->kapasitas - (int) ($kuliahObj->jumlah_mahasiswa ?? 0));
            $score += min(3, intdiv($capacityGap, 25));
        }

        return $score;
    }

    public function applyAssignmentUsage(&$slotUsage, &$roomSlotUsage, &$dosenSlotUsage, &$kelasSlotUsage, &$angkatanSlotUsage, $kuliahId, $slotId, $roomId)
    {
        $slotUsage[$slotId] = ($slotUsage[$slotId] ?? 0) + 1;
        $roomSlotUsage[$slotId][$roomId] = true;

        foreach ($this->dosen_map[$kuliahId] ?? [] as $dosenId) {
            $dosenSlotUsage[$slotId][$dosenId] = ($dosenSlotUsage[$slotId][$dosenId] ?? 0) + 1;
        }

        $kuliahObj = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);
        if (!$kuliahObj) {
            return;
        }

        if ($kuliahObj->kelas_id) {
            $kelasSlotUsage[$slotId][$kuliahObj->kelas_id] = ($kelasSlotUsage[$slotId][$kuliahObj->kelas_id] ?? 0) + 1;
        }

        $angkatanKey = $this->getAcademicGroupKey($kuliahId);
        if ($angkatanKey !== null) {
            $angkatanSlotUsage[$slotId][$angkatanKey] = ($angkatanSlotUsage[$slotId][$angkatanKey] ?? 0) + 1;
        }
    }

    public function getAcademicGroupKey($kuliahId)
    {
        $kuliahObj = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);

        if (!$kuliahObj || !$kuliahObj->kelas) {
            return null;
        }

        return $kuliahObj->kelas->semester . '_' . $kuliahObj->kelas->program_studi_id;
    }

    private function allowedRoomIdsForKuliah($kuliahId, array $roomIds): array
    {
        $kuliah = $this->kuliah[$kuliahId] ?? ($this->manual[$kuliahId] ?? null);
        $kodeMk = $kuliah && isset($kuliah->matakuliah->kode_mk)
            ? (string) $kuliah->matakuliah->kode_mk
            : '';

        if (str_starts_with($kodeMk, 'U')) {
            $allowedRooms = !empty($this->mku_ruang_ids) ? $this->mku_ruang_ids : $roomIds;
        } else {
            $jurusanId = $kuliah && isset($kuliah->matakuliah->jurusan_id)
                ? $kuliah->matakuliah->jurusan_id
                : null;

            $allowedRooms = ($jurusanId && !empty($this->jurusan_ruang_map[$jurusanId]))
                ? $this->jurusan_ruang_map[$jurusanId]
                : $roomIds;
        }

        $candidateRooms = array_values(array_intersect($roomIds, $allowedRooms));

        return !empty($candidateRooms) ? $candidateRooms : $roomIds;
    }

    /* ================= SELECTION ================= */

    public function get_elite()
    {
        $fitness_values = [];

        foreach ($this->fitness as $key => $val)
            $fitness_values[$key] = $val['nilai'];

        arsort($fitness_values);
        return array_slice(array_keys($fitness_values), 0, $this->elite_count);
    }

    public function selection()
    {
        $new = [];

        for ($i = 0; $i < $this->num_crommosom; $i++) {
            $a = array_rand($this->crommosom);
            $b = array_rand($this->crommosom);

            if ($this->fitness[$a]['nilai'] > $this->fitness[$b]['nilai'])
                $new[$i] = $this->crommosom[$a];
            else
                $new[$i] = $this->crommosom[$b];
        }

        $this->crommosom = $new;
    }

    /* ================= CROSSOVER ================= */

    public function crossover()
    {
        $parent = [];

        foreach ($this->crommosom as $key => $val)
            if ((mt_rand() / mt_getrandmax()) <= $this->crossover_rate / 100)
                $parent[] = $key;

        for ($i = 0; $i < count($parent) - 1; $i += 2) {
            if (empty($this->crommosom[0])) continue;

            $point = rand(1, count($this->crommosom[0]) - 2);

            for ($j = $point; $j < count($this->crommosom[0]); $j++) {
                $temp = $this->crommosom[$parent[$i]][$j];
                $this->crommosom[$parent[$i]][$j] = $this->crommosom[$parent[$i + 1]][$j];
                $this->crommosom[$parent[$i + 1]][$j] = $temp;
            }
        }
    }

    /* ================= SMART LOCAL SEARCH MUTATION ================= */

    public function mutation()
    {
        $rate = $this->mutation_rate;

        if ($this->stagnant > 20)
            $rate = 70;

        foreach ($this->crommosom as $i => $cro) {
            if ($this->isCancellationRequested()) {
                return;
            }
            if ($i == $this->best_cromossom)
                continue;

            if ((mt_rand() / mt_getrandmax()) <= $rate / 100) {
                // Get conflicting genes (including fixed schedules awareness)
                $combined = array_merge($this->fixed_schedules, $cro);
                $fixedCount = count($this->fixed_schedules);

                $allConflicts = array_unique(array_merge(
                    array_keys($this->get_clash_dosen($combined)),
                    array_keys($this->get_clash_ruang($combined)),
                    array_keys($this->get_clash_kelas($combined)),
                    array_keys($this->get_clash_angkatan($combined))
                ));

                // Filter to only GA-generated genes
                $conflictGenes = [];
                foreach ($allConflicts as $idx) {
                    if ($idx >= $fixedCount) {
                        $conflictGenes[] = $idx - $fixedCount;
                    }
                }

                foreach ($cro as $idx => $g) {
                    if (
                        empty($g['slot']) ||
                        empty($g['ruang']) ||
                        !array_key_exists($g['slot'], $this->slot) ||
                        !array_key_exists($g['ruang'], $this->ruang)
                    ) {
                        $conflictGenes[] = $idx;
                    }
                }

                $conflictGenes = array_unique($conflictGenes);

                if (empty($conflictGenes)) {
                    $conflictGenes[] = array_rand($cro);
                }

                foreach ($conflictGenes as $index) {
                    if ($this->isCancellationRequested()) {
                        return;
                    }
                    if (!isset($cro[$index])) continue;

                    $kode = $cro[$index]['kuliah'];
                    $bestSlot = $cro[$index]['slot'];
                    $bestRuang = $cro[$index]['ruang'];
                    $minClash = $this->total_clash($cro);

                    $max_try = 15;

                    for ($t = 0; $t < $max_try; $t++) {
                        $validSlotIds = $this->getSlotSesuaiSks($kode);

                        if (empty($validSlotIds)) {
                            continue;
                        }

                        $slot_id = $validSlotIds[array_rand($validSlotIds)];
                        $rid = array_rand($this->ruang);

                        if ($this->ruang[$rid]->kapasitas < $this->kuliah[$kode]->jumlah_mahasiswa)
                            continue;

                        $temp = $cro;
                        $temp[$index]['slot'] = $slot_id;
                        $temp[$index]['ruang'] = $rid;

                        $clash = $this->total_clash($temp);

                        if ($this->success && $clash > 0) {
                            continue;
                        }

                        if ($this->success) {
                            $new_soft = $this->get_dosen_teaching_penalty(array_merge($this->fixed_schedules, $temp))
                                + $this->get_preference_penalty($temp);
                            $old_soft = $this->get_dosen_teaching_penalty(array_merge($this->fixed_schedules, $cro))
                                + $this->get_preference_penalty($cro);

                            if ($new_soft < $old_soft) {
                                $bestSlot = $slot_id;
                                $bestRuang = $rid;
                            }
                        } else {
                            if ($clash < $minClash) {
                                $minClash = $clash;
                                $bestSlot = $slot_id;
                                $bestRuang = $rid;
                            }
                        }
                    }

                    $cro[$index]['slot'] = $bestSlot;
                    $cro[$index]['ruang'] = $bestRuang;

                    $this->repair_gene($cro, $index);
                }

                $this->mark_conflict_as_unassigned($cro);

                $this->repair_unassigned($cro);

                $this->crommosom[$i] = $cro;
            }
        }
    }

    public function repair_gene(&$cro, $index)
    {
        $kode = $cro[$index]['kuliah'];

        $validSlotIds = $this->getSlotSesuaiSks($kode);

        if (empty($validSlotIds)) {
            return;
        }

        $allowedRoomIds = $this->allowedRoomIdsForKuliah($kode, array_keys($this->ruang));

        foreach ($validSlotIds as $slot_id) {
            if ($this->isCancellationRequested()) {
                return;
            }

            foreach ($allowedRoomIds as $rid) {
                $ruang = $this->ruang[$rid] ?? null;
                if (!$ruang) {
                    continue;
                }

                if ($ruang->kapasitas < $this->kuliah[$kode]->jumlah_mahasiswa)
                    continue;

                $conflict = false;

                // Check against fixed schedules
                foreach ($this->fixed_schedules as $fs) {
                    if ($fs['slot'] == $slot_id && $fs['ruang'] == $rid) {
                        $conflict = true;
                        break;
                    }

                    // Dosen conflict with fixed
                    foreach ($this->dosen_map[$kode] ?? [] as $d1) {
                        foreach ($this->dosen_map[$fs['kuliah']] ?? [] as $d2) {
                            if ($d1 == $d2 && $fs['slot'] == $slot_id) {
                                $conflict = true;
                                break 3;
                            }
                        }
                    }
                }

                if ($conflict) continue;

                // Check against other GA genes
                foreach ($cro as $i => $g) {
                    if ($i == $index) continue;
                    if ($this->isUnassignedGene($g)) continue;

                    if ($g['slot'] == $slot_id && $g['ruang'] == $rid) {
                        $conflict = true;
                        break;
                    }

                    foreach ($this->dosen_map[$kode] ?? [] as $d1) {
                        foreach ($this->dosen_map[$g['kuliah']] ?? [] as $d2) {
                            if ($d1 == $d2 && $g['slot'] == $slot_id) {
                                $conflict = true;
                                break 3;
                            }
                        }
                    }

                    $kuliahObj1 = $this->kuliah[$kode] ?? null;
                    $kuliahObj2 = $this->kuliah[$g['kuliah']] ?? null;

                    if ($kuliahObj1 && $kuliahObj2 && $kuliahObj1->kelas && $kuliahObj2->kelas) {
                        $k1 = $kuliahObj1->kelas;
                        $k2 = $kuliahObj2->kelas;

                        if (
                            $k1->semester == $k2->semester &&
                            $k1->program_studi_id == $k2->program_studi_id &&
                            $g['slot'] == $slot_id
                        ) {
                            $conflict = true;
                            break;
                        }
                    }
                }

                if (!$conflict) {
                    $cro[$index]['slot'] = $slot_id;
                    $cro[$index]['ruang'] = $rid;
                    return;
                }
            }
        }
    }

    public function repair_unassigned(&$cro)
    {
        foreach ($cro as $index => $gene) {

            if (
                !empty($gene['slot']) &&
                !empty($gene['ruang'])
            ) {
                continue;
            }

            $kode = $gene['kuliah'];

            foreach ($this->slot as $slot_id => $slot) {

                foreach ($this->ruang as $rid => $ruang) {

                    if ($ruang->kapasitas < $this->kuliah[$kode]->jumlah_mahasiswa) {
                        continue;
                    }

                    $conflict = false;

                    // fixed schedule
                    foreach ($this->fixed_schedules as $fs) {

                        if (
                            $fs['slot'] == $slot_id &&
                            $fs['ruang'] == $rid
                        ) {
                            $conflict = true;
                            break;
                        }

                        foreach ($this->dosen_map[$kode] ?? [] as $d1) {
                            foreach ($this->dosen_map[$fs['kuliah']] ?? [] as $d2) {

                                if (
                                    $d1 == $d2 &&
                                    $fs['slot'] == $slot_id
                                ) {
                                    $conflict = true;
                                    break 3;
                                }
                            }
                        }
                    }

                    if ($conflict) {
                        continue;
                    }

                    // gene lain
                    foreach ($cro as $i => $g) {

                        if ($i == $index) {
                            continue;
                        }

                        if (
                            empty($g['slot']) ||
                            empty($g['ruang'])
                        ) {
                            continue;
                        }

                        if (
                            $g['slot'] == $slot_id &&
                            $g['ruang'] == $rid
                        ) {
                            $conflict = true;
                            break;
                        }

                        foreach ($this->dosen_map[$kode] ?? [] as $d1) {
                            foreach ($this->dosen_map[$g['kuliah']] ?? [] as $d2) {

                                if (
                                    $d1 == $d2 &&
                                    $g['slot'] == $slot_id
                                ) {
                                    $conflict = true;
                                    break 3;
                                }
                            }
                        }
                    }

                    if (!$conflict) {

                        $cro[$index]['slot'] = $slot_id;
                        $cro[$index]['ruang'] = $rid;

                        break 2;
                    }
                }
            }
        }
    }

    public function mark_conflict_as_unassigned(&$cro)
    {
        $combined = array_merge($this->fixed_schedules, $cro);
        $fixedCount = count($this->fixed_schedules);

        $processed = [];

        $conflictGroups = [
            $this->get_clash_dosen($combined),
            $this->get_clash_ruang($combined),
            $this->get_clash_kelas($combined),
            $this->get_clash_angkatan($combined),
        ];

        foreach ($conflictGroups as $group) {

            $indices = array_keys($group);

            sort($indices);

            // simpan gene pertama, korbankan sisanya
            for ($i = 1; $i < count($indices); $i++) {

                $idx = $indices[$i];

                if ($idx < $fixedCount) {
                    continue;
                }

                $geneIndex = $idx - $fixedCount;

                if (!isset($cro[$geneIndex])) {
                    continue;
                }

                if (isset($processed[$geneIndex])) {
                    continue;
                }

                $cro[$geneIndex]['slot'] = null;
                $cro[$geneIndex]['ruang'] = null;

                $processed[$geneIndex] = true;
            }
        }
    }

    /* ================= SAVE ================= */

    public function save_result()
    {
        $cromossom = $this->crommosom[$this->best_cromossom] ?? [];
        logger()->info('legacy_ga_save_result', [
            'best_cromossom_index' => $this->best_cromossom,
            'best_cromossom_size' => count($cromossom),
            'best_fitness' => $this->best_fitness,
            'global_best_fitness' => $this->global_best_fitness,
            'generation' => $this->generation,
            'sample_genes' => array_slice($cromossom, 0, 10),
        ]);

        $assignments = [];

        foreach ($cromossom as $val) {
            $kuliahId = $val['kuliah'] ?? null;

            if (!$kuliahId || $this->isUnassignedGene($val)) {
                continue;
            }

            if (!$this->isSlotSesuaiSks($kuliahId, $val['slot'] ?? null)) {
                continue;
            }

            $assignments[] = [
                'kuliah' => $kuliahId,
                'ruang' => $val['ruang'],
                'slot' => $val['slot'],
            ];
        }

        if (is_callable($this->resultPersister)) {
            ($this->resultPersister)($assignments);
            return;
        }

        foreach ($assignments as $assignment) {
            DB::table('kelas_kuliah')
                ->where('id', $assignment['kuliah'])
                ->update([
                    'ruangan_id' => $assignment['ruang'],
                    'slot_id' => $assignment['slot'],
                    'updated_at' => now(),
                ]);
        }

        return;

        // Save GA results to kelas_kuliah table (ruangan_id + slot_id)
        // Only update unscheduled ones — preserve existing schedules
        foreach ($this->crommosom[$this->best_cromossom] as $val) {
            $kuliahId = $val['kuliah'];
            $ruanganId = $val['ruang'];
            $slotId = $val['slot'];

            if (!$this->isSlotSesuaiSks($kuliahId, $slotId)) {
                throw new \Exception("Slot tidak sesuai SKS untuk kelas_kuliah ID {$kuliahId}. Jadwal tidak disimpan.");
            }

            DB::table('kelas_kuliah')
                ->where('id', $kuliahId)
                ->update([
                    'ruangan_id' => $ruanganId,
                    'slot_id' => $slotId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function hitungRUR()
    {
        $jumlahJadwalQuery = DB::table('jadwal')
            ->join('kelas_kuliah', 'kelas_kuliah.id', '=', 'jadwal.kelas_kuliah_id')
            ->whereNotNull('jadwal.ruangan_id')
            ->whereNotNull('jadwal.slot_id');

        if ($this->program_studi_id) {
            $jumlahJadwalQuery->whereIn('kelas_kuliah.matakuliah_id', function ($query) {
                $query->select('id')
                    ->from('matakuliah')
                    ->where('program_studi_id', $this->program_studi_id);
            });
        } elseif ($this->jurusan_id) {
            $jumlahJadwalQuery->whereIn('kelas_kuliah.matakuliah_id', function ($query) {
                $query->select('id')
                    ->from('matakuliah')
                    ->where('jurusan_id', $this->jurusan_id);
            });
        }

        $jumlah_jadwal = $jumlahJadwalQuery->count();

        $jumlah_ruang = count($this->ruang);
        $jumlah_slot = count($this->slot);

        $total_slot = $jumlah_ruang * $jumlah_slot;

        if ($total_slot == 0) return 0;

        $rur = ($jumlah_jadwal / $total_slot) * 100;

        return round($rur, 2);
    }

    /* ================= CLASH SUMMARY ================= */

    public function getClashSummary()
    {
        $best = $this->crommosom[$this->best_cromossom] ?? [];
        $combined = array_merge($this->fixed_schedules, $best);

        return [
            'dosen' => count($this->get_clash_dosen($combined)),
            'ruang' => count($this->get_clash_ruang($combined)),
            'kelas' => count($this->get_clash_kelas($combined)),
            'angkatan' => count($this->get_clash_angkatan($combined)),
        ];
    }

    /* ================= PRINT UI (returns structured data) ================= */

    public function print_cros($val = [], $key = 0)
    {
        if (empty($val)) return [];

        $fitness = $this->fitness[$key] ?? [];
        $clash_indices = $fitness['all_clash'] ?? [];

        $soft_per_gene = $this->get_soft_per_gene_ui($val);

        $genes = [];
        foreach ($val as $idx => $gen) {
            $is_hard = in_array($idx, $clash_indices);
            $is_soft = isset($soft_per_gene[$idx]);

            $kuliahObj = $this->kuliah[$gen['kuliah']] ?? null;
            $ruangObj = $this->ruang[$gen['ruang']] ?? null;
            $slotObj = $this->slot[$gen['slot']] ?? null;

            $status = 'ok';
            if ($is_hard) {
                $status = 'hard';
            } elseif ($is_soft) {
                $status = 'soft';
            }

            $genes[] = [
                'index' => $idx,
                'kuliah_id' => $gen['kuliah'],
                'ruang_id' => $gen['ruang'],
                'slot_id' => $gen['slot'],
                'nama_mk' => $kuliahObj && $kuliahObj->matakuliah ? $kuliahObj->matakuliah->nama_mk : "KK#{$gen['kuliah']}",
                'nama_kelas' => $kuliahObj && $kuliahObj->kelas ? $kuliahObj->kelas->nama_kelas : '-',
                'ruangan' => $ruangObj ? $ruangObj->ruangan : "R#{$gen['ruang']}",
                'hari' => $slotObj && $slotObj->hari ? $slotObj->hari->nama_hari : '-',
                'waktu' => $slotObj && $slotObj->waktu ? $slotObj->waktu->pukul : '-',
                'status' => $status,
            ];
        }

        return [
            'total_gen' => count($val),
            'hard_count' => count($clash_indices),
            'soft_count' => count($soft_per_gene),
            'fitness' => round($fitness['nilai'] ?? 0, 6),
            'genes' => $genes,
        ];
    }

    public function count_unassigned($cro)
    {
        $count = 0;
        $seenKuliah = [];

        foreach ($cro as $g) {
            $kuliahId = $g['kuliah'] ?? null;
            $slot = $g['slot'] ?? null;
            $ruang = $g['ruang'] ?? null;

            if ($kuliahId) {
                $seenKuliah[$kuliahId] = true;
            }

            if (
                empty($slot) ||
                empty($ruang) ||
                !array_key_exists($slot, $this->slot) ||
                !array_key_exists($ruang, $this->ruang)
            ) {
                $count++;
            }
        }

        foreach ($this->kuliah as $kuliahId => $kuliah) {
            if (!isset($seenKuliah[$kuliahId])) {
                $count++;
            }
        }

        return $count;
    }

    public function get_soft_per_gene_ui($cro)
    {
        $result = [];
        $map = [];

        foreach ($cro as $i => $g) {
            $slotObj = $this->slot[$g['slot']] ?? null;
            if (!$slotObj) continue;

            $hari = $slotObj->hari_id;
            $waktuObj = $this->waktu[$slotObj->waktu_id] ?? null;
            $jam = $waktuObj ? ($waktuObj->jam_index ?? $slotObj->waktu_id) : $slotObj->waktu_id;

            $dosens = $this->dosen_map[$g['kuliah']] ?? [];
            foreach ($dosens as $dosen) {
                $map[$dosen][$hari][$i] = $jam;
            }
        }

        foreach ($map as $dosen => $hari_list) {
            foreach ($hari_list as $hari => $gen_list) {
                asort($gen_list);
                $idx = array_keys($gen_list);
                $jam = array_values($gen_list);

                if (count($jam) > 2) {
                    foreach ($idx as $i) {
                        $result[$i] = true;
                    }
                }

                for ($i = 0; $i < count($jam) - 1; $i++) {
                    if (abs($jam[$i] - $jam[$i + 1]) == 1) {
                        $result[$idx[$i]] = true;
                        $result[$idx[$i + 1]] = true;
                    }
                }
            }
        }

        return $result;
    }
}
