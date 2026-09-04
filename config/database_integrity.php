<?php

return [
    'relations' => [
        ['child_table' => 'users', 'child_column' => 'role_id', 'parent_table' => 'role', 'parent_column' => 'id'],
        ['child_table' => 'users', 'child_column' => 'dosen_id', 'parent_table' => 'dosen', 'parent_column' => 'id'],
        ['child_table' => 'users', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'users', 'child_column' => 'program_studi_id', 'parent_table' => 'program_studi', 'parent_column' => 'id'],
        ['child_table' => 'program_studi', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'dosen', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'matakuliah', 'child_column' => 'program_studi_id', 'parent_table' => 'program_studi', 'parent_column' => 'id'],
        ['child_table' => 'matakuliah', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'kelas', 'child_column' => 'program_studi_id', 'parent_table' => 'program_studi', 'parent_column' => 'id'],
        ['child_table' => 'kelas', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah', 'child_column' => 'dosen_id', 'parent_table' => 'dosen', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah', 'child_column' => 'matakuliah_id', 'parent_table' => 'matakuliah', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah', 'child_column' => 'kelas_id', 'parent_table' => 'kelas', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah_dosen', 'child_column' => 'kelas_kuliah_id', 'parent_table' => 'kelas_kuliah', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah_dosen', 'child_column' => 'dosen_id', 'parent_table' => 'dosen', 'parent_column' => 'id'],
        ['child_table' => 'kelas_kuliah_dosen', 'child_column' => 'preferred_slot_id', 'parent_table' => 'slot', 'parent_column' => 'id'],
        ['child_table' => 'ruangan', 'child_column' => 'gedung_id', 'parent_table' => 'gedung', 'parent_column' => 'id'],
        ['child_table' => 'slot', 'child_column' => 'hari_id', 'parent_table' => 'hari', 'parent_column' => 'id'],
        ['child_table' => 'slot', 'child_column' => 'waktu_id', 'parent_table' => 'waktu', 'parent_column' => 'id'],
        ['child_table' => 'jurusan_ruangan', 'child_column' => 'jurusan_id', 'parent_table' => 'jurusan', 'parent_column' => 'id'],
        ['child_table' => 'jurusan_ruangan', 'child_column' => 'ruangan_id', 'parent_table' => 'ruangan', 'parent_column' => 'id'],
        ['child_table' => 'jadwal', 'child_column' => 'kelas_kuliah_id', 'parent_table' => 'kelas_kuliah', 'parent_column' => 'id'],
        ['child_table' => 'jadwal', 'child_column' => 'slot_id', 'parent_table' => 'slot', 'parent_column' => 'id'],
        ['child_table' => 'jadwal', 'child_column' => 'ruangan_id', 'parent_table' => 'ruangan', 'parent_column' => 'id'],
    ],

    'duplicate_checks' => [
        ['table' => 'slot', 'columns' => ['hari_id', 'waktu_id'], 'label' => 'slot(hari_id, waktu_id)'],
        ['table' => 'jadwal', 'columns' => ['slot_id', 'ruangan_id'], 'label' => 'jadwal(slot_id, ruangan_id)'],
        ['table' => 'jurusan_ruangan', 'columns' => ['jurusan_id', 'ruangan_id'], 'label' => 'jurusan_ruangan(jurusan_id, ruangan_id)'],
    ],

    'expected_roles' => [
        0 => 'Admin Fakultas',
        1 => 'Wakil Dekan I Bidang Akademik',
        2 => 'Sub-Koordinator Bidang Akademik',
        3 => 'Admin Jurusan',
        5 => 'Dosen',
        6 => 'Ketua Jurusan',
        7 => 'Koordinator Program Studi',
        999 => 'Super Admin',
    ],
];
