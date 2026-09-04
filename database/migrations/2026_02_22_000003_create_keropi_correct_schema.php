<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration creates the EXACT schema from keropi_prod.sql
     */
    public function up(): void
    {
        // 1. HARI (Days of Week)
        Schema::create('hari', function (Blueprint $table) {
            $table->id();
            $table->string('nama_hari', 50);
            $table->timestamps();
        });

        // 2. WAKTU (Time Periods)
        // Schema with time periods and SKS values
        Schema::create('waktu', function (Blueprint $table) {
            $table->id();
            $table->string('pukul', 50); // e.g., "08.00-09.40"
            $table->integer('sks'); // Credit hours
            $table->timestamps();
        });

        // 3. SLOT (Combination of Hari + Waktu)
        Schema::create('slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hari_id')->constrained('hari')->onDelete('cascade');
            $table->foreignId('waktu_id')->constrained('waktu')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['hari_id', 'waktu_id']); // Each day-time combo once
        });

        // 4. GEDUNG (Buildings)
        Schema::create('gedung', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gedung', 150);
            $table->timestamps();
        });

        // 5. JURUSAN (Department)
        Schema::create('jurusan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jurusan', 150);
            $table->timestamps();
        });

        // 6. PROGRAM STUDI (Study Program)
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('restrict');
            $table->string('nama_prodi', 150);
            $table->timestamps();
        });

        // 7. RUANGAN (Classrooms/Rooms)
        Schema::create('ruangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gedung_id')->constrained('gedung')->onDelete('restrict');
            $table->string('ruangan', 100);
            $table->integer('kapasitas');
            $table->timestamps();
        });

        // 8. DOSEN (Lecturers/Professors)
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('restrict');
            $table->string('nip', 50)->nullable()->unique(); // NIP can be null
            $table->string('nama_lengkap', 150);
            $table->string('inisial', 20)->nullable();
            $table->json('preferences')->nullable(); // For genetic algorithm preferences
            $table->timestamps();
        });

        // 9. MATAKULIAH (Courses/Subjects)
        Schema::create('matakuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_studi_id')->constrained('program_studi')->onDelete('restrict');
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('restrict');
            $table->string('kode_mk', 50);
            $table->string('nama_mk', 200);
            $table->integer('sks');
            $table->integer('semester');
            $table->timestamps();
        });

        // 10. KELAS (Classes)
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_studi_id')->constrained('program_studi')->onDelete('cascade');
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('cascade');
            $table->string('nama_kelas', 100);
            $table->integer('semester');
            $table->timestamps();
        });

        // 11. KELAS_KULIAH (Core linking table: Lecturer + Course + Class + Student Count)
        Schema::create('kelas_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->onDelete('restrict');
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->onDelete('restrict');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('restrict');
            $table->integer('jumlah_mahasiswa')->default(0);
            $table->foreignId('ruangan_id')->nullable()->constrained('ruangan')->onDelete('set null');
            $table->foreignId('slot_id')->nullable()->constrained('slot')->onDelete('set null');
            $table->timestamps();
        });

        // 12. JADWAL (Scheduled Sessions)
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_kuliah_id')->constrained('kelas_kuliah')->onDelete('cascade');
            $table->foreignId('slot_id')->constrained('slot')->onDelete('restrict');
            $table->foreignId('ruangan_id')->constrained('ruangan')->onDelete('restrict');
            $table->boolean('is_manual')->default(false); // Hybrid scheduling flag
            $table->timestamps();
        });

        // 13. ROLE (User Roles)
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('role', 100);
            $table->timestamps();
        });

        // 14. USERS (System Users)
        // Note: First check if users table already exists from Laravel default
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('nama_user', 150);
                $table->foreignId('dosen_id')->nullable()->constrained('dosen')->onDelete('set null');
                $table->string('email', 150)->unique();
                $table->string('password', 255);
                $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->onDelete('set null');
                $table->foreignId('program_studi_id')->nullable()->constrained('program_studi')->onDelete('set null');
                $table->foreignId('role_id')->constrained('role')->onDelete('restrict');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('jadwal');
        Schema::dropIfExists('kelas_kuliah');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('matakuliah');
        Schema::dropIfExists('dosen');
        Schema::dropIfExists('ruangan');
        Schema::dropIfExists('program_studi');
        Schema::dropIfExists('jurusan');
        Schema::dropIfExists('gedung');
        Schema::dropIfExists('slot');
        Schema::dropIfExists('waktu');
        Schema::dropIfExists('hari');
        // Don't drop role and users as they may have been created by other migrations
    }
};
