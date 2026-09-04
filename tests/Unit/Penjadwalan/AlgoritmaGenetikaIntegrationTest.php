<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Infrastructure\Scheduling\Legacy\AlgoritmaGenetika;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlgoritmaGenetikaIntegrationTest extends TestCase
{
    public function test_save_result_sends_only_valid_assignments_to_result_persister(): void
    {
        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'sks' => 2]]),
            collect([(object) ['id' => 10, 'kapasitas' => 40]]),
            collect([$this->fakeKelasKuliah(100, 1, 1, 2)]),
            collect(),
            collect([(object) ['id' => 20, 'hari_id' => 1, 'waktu_id' => 1]]),
        );

        $algorithm->best_cromossom = 0;
        $algorithm->crommosom = [
            [
                ['kuliah' => 100, 'ruang' => 10, 'slot' => 20],
                ['kuliah' => 101, 'ruang' => null, 'slot' => null],
                ['kuliah' => 102, 'ruang' => 999, 'slot' => 20],
                ['kuliah' => 103, 'ruang' => 10, 'slot' => 999],
            ],
        ];

        $persisted = null;
        $algorithm->resultPersister = function (array $assignments) use (&$persisted): void {
            $persisted = $assignments;
        };

        $algorithm->save_result();

        $this->assertSame([
            ['kuliah' => 100, 'ruang' => 10, 'slot' => 20],
        ], $persisted);
    }

    public function test_hitung_rur_counts_schedules_from_jadwal_table(): void
    {
        $this->seedSingleScheduledClass();

        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'sks' => 2]]),
            collect([(object) ['id' => 1, 'kapasitas' => 40]]),
            collect(),
            collect(),
            collect([(object) ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1]]),
        );
        $algorithm->program_studi_id = 1;

        $this->assertSame(100.0, $algorithm->hitungRUR());
    }

    public function test_teaching_gap_penalty_follows_latest_jam_index_rule(): void
    {
        $algorithm = new AlgoritmaGenetika(
            collect([
                (object) ['id' => 1, 'pukul' => '13.00 - 14.40', 'sks' => 2, 'jam_index' => 5],
                (object) ['id' => 2, 'pukul' => '13:00 - 15:30', 'sks' => 3, 'jam_index' => 5],
            ]),
            collect(),
            collect(),
            collect(),
            collect([
                (object) ['id' => 10, 'hari_id' => 1, 'waktu_id' => 1],
                (object) ['id' => 11, 'hari_id' => 1, 'waktu_id' => 2],
            ]),
        );
        $algorithm->dosen_map = [
            100 => [7],
            101 => [7],
        ];

        $this->assertSame(0, $algorithm->get_dosen_teaching_penalty([
            ['kuliah' => 100, 'ruang' => 1, 'slot' => 10],
            ['kuliah' => 101, 'ruang' => 2, 'slot' => 11],
        ]));
    }

    public function test_repair_gene_uses_only_rooms_allocated_to_course_jurusan(): void
    {
        $kuliah = new class {
            public int $id = 100;
            public int $jumlah_mahasiswa = 20;
            public int $kelas_id = 1;
            public ?int $dosen_id = null;
            public object $kelas;
            public object $matakuliah;

            public function __construct()
            {
                $this->kelas = (object) ['semester' => 1, 'program_studi_id' => 1];
                $this->matakuliah = (object) ['jurusan_id' => 1, 'kode_mk' => 'TE001', 'sks' => 2];
            }

            public function relationLoaded(string $relation): bool
            {
                return false;
            }
        };
        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]]),
            collect([
                (object) ['id' => 10, 'kapasitas' => 40],
                (object) ['id' => 20, 'kapasitas' => 40],
            ]),
            collect([$kuliah]),
            collect(),
            collect([(object) ['id' => 30, 'hari_id' => 1, 'waktu_id' => 1]]),
        );
        $algorithm->jurusan_ruang_map = [1 => [20]];

        $chromosome = [
            ['kuliah' => 100, 'ruang' => 10, 'slot' => 30],
        ];

        $algorithm->repair_gene($chromosome, 0);

        $this->assertSame(20, $chromosome[0]['ruang']);
    }

    public function test_random_chromosome_assigns_each_course_to_matching_sks_slot(): void
    {
        $twoSks = $this->fakeKelasKuliah(100, 1, 1, 2);
        $threeSks = $this->fakeKelasKuliah(101, 2, 2, 3);

        $algorithm = new AlgoritmaGenetika(
            collect([
                (object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2],
                (object) ['id' => 2, 'pukul' => '10.00 - 12.30', 'sks' => 3],
            ]),
            collect([
                (object) ['id' => 10, 'kapasitas' => 40],
                (object) ['id' => 20, 'kapasitas' => 40],
            ]),
            collect([$twoSks, $threeSks]),
            collect(),
            collect([
                (object) ['id' => 200, 'hari_id' => 1, 'waktu_id' => 1],
                (object) ['id' => 300, 'hari_id' => 1, 'waktu_id' => 2],
            ]),
        );

        $chromosome = $algorithm->get_rand_crommosom();
        $slotsByKuliah = collect($chromosome)->pluck('slot', 'kuliah')->all();

        $this->assertSame(200, $slotsByKuliah[100]);
        $this->assertSame(300, $slotsByKuliah[101]);
    }

    public function test_save_result_does_not_persist_assignment_with_mismatched_sks_slot(): void
    {
        $twoSks = $this->fakeKelasKuliah(100, 1, 1, 2);

        $algorithm = new AlgoritmaGenetika(
            collect([
                (object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2],
                (object) ['id' => 2, 'pukul' => '10.00 - 12.30', 'sks' => 3],
            ]),
            collect([(object) ['id' => 10, 'kapasitas' => 40]]),
            collect([$twoSks]),
            collect(),
            collect([
                (object) ['id' => 200, 'hari_id' => 1, 'waktu_id' => 1],
                (object) ['id' => 300, 'hari_id' => 1, 'waktu_id' => 2],
            ]),
        );
        $algorithm->best_cromossom = 0;
        $algorithm->crommosom = [
            [
                ['kuliah' => 100, 'ruang' => 10, 'slot' => 300],
            ],
        ];

        $persisted = null;
        $algorithm->resultPersister = function (array $assignments) use (&$persisted): void {
            $persisted = $assignments;
        };

        $algorithm->save_result();

        $this->assertSame([], $persisted);
    }

    public function test_unassigned_gene_is_capped_below_valid_solution_even_with_soft_penalty(): void
    {
        $kuliah = new class {
            public int $id = 100;
            public int $jumlah_mahasiswa = 20;
            public int $kelas_id = 1;
            public ?int $dosen_id = 7;
            public object $kelas;
            public object $matakuliah;

            public function __construct()
            {
                $this->kelas = (object) ['semester' => 1, 'program_studi_id' => 1];
                $this->matakuliah = (object) ['jurusan_id' => 1, 'kode_mk' => 'TE001'];
            }

            public function relationLoaded(string $relation): bool
            {
                return false;
            }
        };

        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]]),
            collect([(object) ['id' => 10, 'kapasitas' => 40]]),
            collect([$kuliah]),
            collect(),
            collect([(object) ['id' => 20, 'hari_id' => 1, 'waktu_id' => 1]]),
        );
        $algorithm->dosen_preference = [100 => [7 => 999]];
        $algorithm->crommosom = [
            [
                ['kuliah' => 100, 'ruang' => null, 'slot' => null],
            ],
        ];

        $algorithm->calculate_fitness(0);

        $this->assertSame(0.99, $algorithm->fitness[0]['nilai']);
    }

    public function test_fixed_manual_conflicts_are_not_reported_as_generated_hard_conflicts(): void
    {
        $manualOne = $this->fakeKelasKuliah(1, 1, 1);
        $manualTwo = $this->fakeKelasKuliah(2, 2, 1);
        $generated = $this->fakeKelasKuliah(3, 3, 2);

        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]]),
            collect([
                (object) ['id' => 10, 'kapasitas' => 40],
                (object) ['id' => 20, 'kapasitas' => 40],
            ]),
            collect([$generated]),
            collect([
                $this->withSchedule($manualOne, 10, 100),
                $this->withSchedule($manualTwo, 20, 100),
            ]),
            collect([
                (object) ['id' => 100, 'hari_id' => 1, 'waktu_id' => 1],
                (object) ['id' => 200, 'hari_id' => 1, 'waktu_id' => 1],
            ]),
        );
        $algorithm->crommosom = [
            [
                ['kuliah' => 3, 'ruang' => 10, 'slot' => 200],
            ],
        ];
        $algorithm->best_cromossom = 0;

        $algorithm->calculate_fitness(0);

        $this->assertSame(1.0, $algorithm->fitness[0]['nilai']);
        $this->assertSame(0, $algorithm->fitness[0]['hard_clash']);
        $this->assertSame([
            'dosen' => 0,
            'ruang' => 0,
            'kelas' => 0,
            'angkatan' => 2,
        ], $algorithm->getClashSummary());
    }

    public function test_angkatan_clash_follows_latest_algorithm_hard_fitness(): void
    {
        $first = $this->fakeKelasKuliah(10, 1, 1);
        $second = $this->fakeKelasKuliah(20, 2, 1);

        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]]),
            collect([
                (object) ['id' => 10, 'kapasitas' => 40],
                (object) ['id' => 20, 'kapasitas' => 40],
            ]),
            collect([$first, $second]),
            collect(),
            collect([(object) ['id' => 100, 'hari_id' => 1, 'waktu_id' => 1]]),
        );
        $algorithm->crommosom = [
            [
                ['kuliah' => 10, 'ruang' => 10, 'slot' => 100],
                ['kuliah' => 20, 'ruang' => 20, 'slot' => 100],
            ],
        ];

        $algorithm->calculate_fitness(0);

        $this->assertSame(2, $algorithm->fitness[0]['hard_clash']);
        $this->assertSame(1 / 3, $algorithm->fitness[0]['nilai']);
        $this->assertSame(2, $algorithm->getClashSummary()['angkatan']);
    }

    public function test_generate_keeps_cancel_flag_and_writes_canceled_progress(): void
    {
        $userId = 'cancel-test-user';
        Cache::store('database')->put('ga_cancel_' . $userId, true, 3600);

        $kuliah = new class {
            public int $id = 100;
            public ?int $dosen_id = null;

            public function relationLoaded(string $relation): bool
            {
                return false;
            }
        };

        $algorithm = new AlgoritmaGenetika(
            collect([(object) ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]]),
            collect([(object) ['id' => 10, 'kapasitas' => 40]]),
            collect([$kuliah]),
            collect(),
            collect([(object) ['id' => 20, 'hari_id' => 1, 'waktu_id' => 1]]),
        );
        $algorithm->user_id = $userId;
        $algorithm->num_crommosom = 2;
        $algorithm->max_generation = 2;

        $algorithm->generate();

        $progress = Cache::store('database')->get('ga_progress_' . $userId);

        $this->assertTrue($algorithm->isCanceled);
        $this->assertTrue(Cache::store('database')->get('ga_cancel_' . $userId));
        $this->assertSame('canceled', $progress['status'] ?? null);
    }

    private function fakeKelasKuliah(int $id, int $dosenId, int $semester, int $sks = 2): object
    {
        return new class ($id, $dosenId, $semester, $sks) {
            public int $id;
            public int $jumlah_mahasiswa = 20;
            public int $kelas_id;
            public int $dosen_id;
            public object $kelas;
            public object $matakuliah;
            public ?int $ruangan_id = null;
            public ?int $slot_id = null;

            public function __construct(int $id, int $dosenId, int $semester, int $sks)
            {
                $this->id = $id;
                $this->kelas_id = $id;
                $this->dosen_id = $dosenId;
                $this->kelas = (object) ['semester' => $semester, 'program_studi_id' => 1];
                $this->matakuliah = (object) ['jurusan_id' => 1, 'kode_mk' => "TE{$id}", 'sks' => $sks];
            }

            public function relationLoaded(string $relation): bool
            {
                return false;
            }
        };
    }

    private function withSchedule(object $kelasKuliah, int $ruanganId, int $slotId): object
    {
        $kelasKuliah->ruangan_id = $ruanganId;
        $kelasKuliah->slot_id = $slotId;

        return $kelasKuliah;
    }

    private function seedSingleScheduledClass(): void
    {
        DB::table('hari')->insert([
            ['id' => 1, 'nama_hari' => 'Senin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2, 'jam_index' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('jurusan')->insert([
            ['id' => 1, 'nama_jurusan' => 'Teknik Informatika', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('gedung')->insert([
            ['id' => 1, 'nama_gedung' => 'A', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'A101', 'kapasitas' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('program_studi')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'Teknik Informatika', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('dosen')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nip' => '111', 'nama_lengkap' => 'Dosen Satu', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('matakuliah')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'IF001', 'nama_mk' => 'Algoritma', 'sks' => 2, 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('kelas')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'IF-A', 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('kelas_kuliah')->insert([
            ['id' => 1, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('jadwal')->insert([
            ['id' => 1, 'kelas_kuliah_id' => 1, 'slot_id' => 1, 'ruangan_id' => 1, 'origin' => 'generated', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
