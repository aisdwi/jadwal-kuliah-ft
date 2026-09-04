<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

final class KelasKuliahEloquentRelations
{
    public const WITHS = [
        'dosen:id,jurusan_id,nama_lengkap,inisial',
        'dosen.jurusan:id,nama_jurusan',
        'dosens:id,jurusan_id,nama_lengkap,inisial',
        'dosens.jurusan:id,nama_jurusan',
        'matakuliah:id,program_studi_id,jurusan_id,nama_mk,kode_mk,sks,semester',
        'matakuliah.programStudi:id,jurusan_id,nama_prodi',
        'matakuliah.programStudi.jurusan:id,nama_jurusan',
        'kelas:id,nama_kelas,semester,program_studi_id,jurusan_id',
        'kelas.programStudi:id,nama_prodi',
        'kelas.jurusan:id,nama_jurusan',
        'jadwals:id,kelas_kuliah_id,slot_id,ruangan_id,origin,scheduling_run_id',
        'jadwals.slot:id,hari_id,waktu_id',
        'jadwals.slot.hari:id,nama_hari',
        'jadwals.slot.waktu:id,pukul,sks',
        'jadwals.ruangan:id,ruangan,kapasitas',
    ];
}
