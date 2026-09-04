<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportLegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------
        // TAHAP 0: BERSIH-BERSIH (Agar Tidak Duplikat/Error)
        // ---------------------------------------------------------
        Schema::disableForeignKeyConstraints();
        DB::table('mata_kuliahs')->truncate();
        DB::table('dosens')->truncate();
        DB::table('ruangans')->truncate();
        DB::table('jurusans')->truncate();
        Schema::enableForeignKeyConstraints();

        $this->command->warn('🧹 Data lama di tabel master telah dibersihkan.');

        // ---------------------------------------------------------
        // TAHAP 1: IMPORT PRODI (Disimpan di tabel 'jurusans')
        // ---------------------------------------------------------
        $oldJurusans = DB::table('program_studi')->get();
        $jurusanMap = []; 

        foreach ($oldJurusans as $old) {
            $newId = Str::uuid();
            $jurusanMap[$old->id] = $newId; 

            // Nama Asli
            $namaJurusan = $old->nama_prodi;

            // Kode Asli (Slug dari nama, misal: s1-teknik-informatika)
            $kodeJurusan = Str::slug($namaJurusan); 

            DB::table('jurusans')->insert([
                'id' => $newId,
                'kode_jurusan' => $kodeJurusan, 
                'nama_jurusan' => $namaJurusan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('✅ Prodi berhasil diimport! Total: ' . count($oldJurusans));


        // ---------------------------------------------------------
        // TAHAP 2: IMPORT DOSEN
        // ---------------------------------------------------------
        $oldDosens = DB::table('dosen')->get();
        foreach ($oldDosens as $old) {
            $cleanNip = $old->nip;
            if ($cleanNip == 0 || $cleanNip === '0' || $cleanNip === '-') {
                $cleanNip = null;
            }

            DB::table('dosens')->insert([
                'id' => Str::uuid(),
                'nip' => $cleanNip,
                'nama_lengkap' => $old->nama_lengkap,
                'inisial' => $old->inisial,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('✅ Dosen berhasil diimport! Total: ' . count($oldDosens));


        // ---------------------------------------------------------
        // TAHAP 3: IMPORT MATA KULIAH (FILTER DUPLIKAT)
        // ---------------------------------------------------------
        $oldMatkuls = DB::table('matakuliah')->get();
        $processedMatkuls = []; 

        $countMasuk = 0;
        $countSkip = 0;

        foreach ($oldMatkuls as $old) {
            $newJurusanId = isset($old->program_studi_id) ? ($jurusanMap[$old->program_studi_id] ?? null) : null;
            
            // PERBAIKAN UTAMA: Menggunakan 'kode_mk' dan 'nama_mk' sesuai Tinker
            $kodeMk = $old->kode_mk ?? 'UNK';
            $namaMk = $old->nama_mk ?? 'Tanpa Nama';
            
            // LOGIKA ANTI DUPLIKAT
            $uniqueKey = $kodeMk . '-' . $newJurusanId;

            if (in_array($uniqueKey, $processedMatkuls)) {
                $countSkip++;
                continue; 
            }

            $processedMatkuls[] = $uniqueKey;

            DB::table('mata_kuliahs')->insert([
                'id' => Str::uuid(),
                'kode_mk' => $kodeMk, // Sekarang akan ambil "FTS 1101"
                'nama_mk' => $namaMk, // Sekarang akan ambil "Fisika Dasar"
                'sks' => $old->sks ?? 2,
                'semester' => $old->semester ?? 1,
                'jurusan_id' => $newJurusanId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $countMasuk++;
        }
        $this->command->info("✅ Mata Kuliah berhasil diimport! (Masuk: $countMasuk, Duplikat Dibuang: $countSkip)");


        // ---------------------------------------------------------
        // TAHAP 4: IMPORT RUANGAN
        // ---------------------------------------------------------
        $oldRuangans = DB::table('ruangan')->get();
        foreach ($oldRuangans as $old) {
            $namaRuangan = $old->ruangan; 

            DB::table('ruangans')->insert([
                'id' => Str::uuid(),
                'kode_ruangan' => strtoupper($namaRuangan), 
                'nama_ruangan' => $namaRuangan,
                'kapasitas' => $old->kapasitas ?? 40,
                'jenis' => 'Teori',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('✅ Ruangan berhasil diimport! Total: ' . count($oldRuangans));
    }
}