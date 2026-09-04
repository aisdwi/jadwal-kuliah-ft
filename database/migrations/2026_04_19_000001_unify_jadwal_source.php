<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 — Unifikasi Sumber Jadwal
 *
 * Masalah legacy (ANALISIS_ARSITEKTUR_LEGACY.md poin #9):
 *   Dua sumber kebenaran jadwal:
 *   (a) tabel `jadwal`                             → hasil Genetic Algorithm
 *   (b) kolom `slot_id`/`ruangan_id` di `kelas_kuliah` → jadwal manual
 *
 * Solusi (PLAN_CLEAN_ARCHITECTURE.md Fase 2):
 *   - Semua penugasan masuk tabel `jadwal` (satu sumber kebenaran)
 *   - `origin ENUM('manual','generated')` mencatat asal penugasan
 *   - UNIQUE(slot_id, ruangan_id) mencegah double-booking di level DB
 *   - Kolom `slot_id` & `ruangan_id` di `kelas_kuliah` dihapus
 *
 * Catatan: Migrasi ini IDEMPOTENT — setiap langkah dicek hasColumn/hasIndex
 *          sehingga aman dijalankan ulang jika sebelumnya gagal sebagian.
 *
 * Referensi teori:
 *   Martin, Clean Architecture Ch. 30 hal. 275 — "The Database Is a Detail"
 *   SWEBOK v4 Ch. 7 §2.1.4 — maintainability & technical debt
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── LANGKAH 1 ──────────────────────────────────────────────────────────
        // Tambah kolom `origin` ke jadwal (jika belum ada)
        if (! Schema::hasColumn('jadwal', 'origin')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->enum('origin', ['manual', 'generated'])
                      ->default('generated')
                      ->after('ruangan_id')
                      ->comment('Asal: manual (drag-and-drop) atau generated (GA)');
            });
        }

        // ── LANGKAH 2 ──────────────────────────────────────────────────────────
        // Konversi is_manual → origin (hanya jika kolom is_manual ada di DB)
        if (Schema::hasColumn('jadwal', 'is_manual')) {
            DB::table('jadwal')->where('is_manual', true)->update(['origin' => 'manual']);
            DB::table('jadwal')->where('is_manual', false)->update(['origin' => 'generated']);
        }
        // Jika is_manual tidak ada, semua baris sudah ber-origin='generated' (default).
        // Baris manual akan di-set di Langkah 3.

        // ── LANGKAH 3 ──────────────────────────────────────────────────────────
        // Migrasikan penugasan manual dari kelas_kuliah ke jadwal
        // (hanya jika kolom slot_id + ruangan_id masih ada di kelas_kuliah)
        if (Schema::hasColumn('kelas_kuliah', 'slot_id')
            && Schema::hasColumn('kelas_kuliah', 'ruangan_id')
        ) {
            $manualAssignments = DB::table('kelas_kuliah')
                ->whereNotNull('slot_id')
                ->whereNotNull('ruangan_id')
                ->select('id as kelas_kuliah_id', 'slot_id', 'ruangan_id')
                ->get();

            foreach ($manualAssignments as $assignment) {
                $exists = DB::table('jadwal')
                    ->where('kelas_kuliah_id', $assignment->kelas_kuliah_id)
                    ->where('slot_id', $assignment->slot_id)
                    ->where('ruangan_id', $assignment->ruangan_id)
                    ->exists();

                if (! $exists) {
                    DB::table('jadwal')->insert([
                        'kelas_kuliah_id' => $assignment->kelas_kuliah_id,
                        'slot_id'         => $assignment->slot_id,
                        'ruangan_id'      => $assignment->ruangan_id,
                        'origin'          => 'manual',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                } else {
                    // Baris sudah ada → tandai sebagai manual
                    DB::table('jadwal')
                        ->where('kelas_kuliah_id', $assignment->kelas_kuliah_id)
                        ->where('slot_id', $assignment->slot_id)
                        ->where('ruangan_id', $assignment->ruangan_id)
                        ->update(['origin' => 'manual']);
                }
            }
        }

        // ── LANGKAH 4 ──────────────────────────────────────────────────────────
        // Tambah UNIQUE(slot_id, ruangan_id) di jadwal (jika belum ada)
        // Mencegah double-booking di level database.
        if (! Schema::hasIndex('jadwal', 'jadwal_slot_ruangan_unique')) {
            // Hapus duplikat terlebih dahulu (jaga-jaga jika ada data kotor)
            $duplicates = DB::table('jadwal')
                ->select('slot_id', 'ruangan_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
                ->whereNotNull('slot_id')
                ->whereNotNull('ruangan_id')
                ->groupBy('slot_id', 'ruangan_id')
                ->having('c', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                DB::table('jadwal')
                    ->where('slot_id', $dup->slot_id)
                    ->where('ruangan_id', $dup->ruangan_id)
                    ->where('id', '!=', $dup->keep_id)
                    ->delete();
            }

            Schema::table('jadwal', function (Blueprint $table) {
                $table->unique(['slot_id', 'ruangan_id'], 'jadwal_slot_ruangan_unique');
            });
        }

        // ── LANGKAH 5 ──────────────────────────────────────────────────────────
        // Hapus kolom is_manual dari jadwal (jika masih ada)
        if (Schema::hasColumn('jadwal', 'is_manual')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->dropColumn('is_manual');
            });
        }

        // ── LANGKAH 6 ──────────────────────────────────────────────────────────
        // Hapus slot_id + ruangan_id dari kelas_kuliah (jika masih ada)
        // Note: SQLite has severe limitations with dropping FK columns,
        // so we skip this for SQLite. The data has been migrated; columns are harmless.
        $connection = DB::connection()->getDriverName();
        if ($connection !== 'sqlite' && (Schema::hasColumn('kelas_kuliah', 'slot_id')
            || Schema::hasColumn('kelas_kuliah', 'ruangan_id'))
        ) {
            Schema::table('kelas_kuliah', function (Blueprint $table) {
                $toDrop = [];
                if (Schema::hasColumn('kelas_kuliah', 'slot_id')) {
                    $toDrop[] = 'slot_id';
                }
                if (Schema::hasColumn('kelas_kuliah', 'ruangan_id')) {
                    $toDrop[] = 'ruangan_id';
                }
                
                if (! empty($toDrop)) {
                    foreach ($toDrop as $column) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // ── ROLLBACK ───────────────────────────────────────────────────────────

        // 1. Kembalikan slot_id + ruangan_id ke kelas_kuliah
        if (! Schema::hasColumn('kelas_kuliah', 'slot_id')) {
            Schema::table('kelas_kuliah', function (Blueprint $table) {
                $table->foreignId('ruangan_id')->nullable()
                      ->constrained('ruangan')->onDelete('set null');
                $table->foreignId('slot_id')->nullable()
                      ->constrained('slot')->onDelete('set null');
            });

            // Isi kembali dari jadwal dengan origin='manual'
            DB::table('jadwal')->where('origin', 'manual')
                ->select('kelas_kuliah_id', 'slot_id', 'ruangan_id')
                ->each(function ($row) {
                    DB::table('kelas_kuliah')->where('id', $row->kelas_kuliah_id)
                        ->update(['slot_id' => $row->slot_id, 'ruangan_id' => $row->ruangan_id]);
                });
        }

        // 2. Hapus unique constraint
        if (Schema::hasIndex('jadwal', 'jadwal_slot_ruangan_unique')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->dropUnique('jadwal_slot_ruangan_unique');
            });
        }

        // 3. Hapus kolom origin
        if (Schema::hasColumn('jadwal', 'origin')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->dropColumn('origin');
            });
        }
    }
};
