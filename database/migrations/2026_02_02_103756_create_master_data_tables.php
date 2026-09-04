<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Jurusan (Program Studi)
        // Menyimpan data jurusan seperti Teknik Informatika, Sistem Informasi, dll.
        Schema::create('jurusans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_jurusan')->unique(); // Contoh: IF, SI
            $table->string('nama_jurusan');
            $table->timestamps();
        });

        // 2. Tabel Dosen
        // Menyimpan data pengajar
        Schema::create('dosens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip')->unique()->nullable(); // NIP bisa kosong jika dosen luar biasa
            $table->string('nama_lengkap');
            $table->string('inisial')->nullable(); // Contoh: BDI (untuk tampilan singkat di jadwal)
            $table->timestamps();
        });

        // 3. Tabel Mata Kuliah
        // Menyimpan daftar pelajaran
        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_mk'); // Contoh: TIF123
            $table->string('nama_mk');
            $table->integer('sks');
            $table->string('semester'); 
            $table->foreignUuid('jurusan_id')->nullable()->index(); 
            $table->timestamps();
        });

        // 4. Tabel Ruangan
        // Menyimpan daftar kelas/lab
        Schema::create('ruangans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_ruangan')->unique(); // Contoh: R-101, LAB-A
            $table->string('nama_ruangan');
            $table->integer('kapasitas')->default(0);
            $table->string('jenis')->nullable(); // Contoh: 'Teori', 'Laboratorium'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PENTING: Hapus tabel dengan urutan terbalik dari pembuatan
        // Hapus child dulu (yang punya foreign key), baru parent-nya
        Schema::dropIfExists('ruangans');
        Schema::dropIfExists('mata_kuliahs');
        Schema::dropIfExists('dosens');
        Schema::dropIfExists('jurusans');
    }
};