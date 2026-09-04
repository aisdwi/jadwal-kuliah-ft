<?php

namespace Tests\Unit\KelasKuliah;

use App\Modules\KelasKuliah\Presentation\Http\Support\KelasKuliahSubjectFormatter;
use PHPUnit\Framework\TestCase;

class KelasKuliahSubjectFormatterTest extends TestCase
{
    public function test_formats_course_and_class_label(): void
    {
        $class = KelasKuliahSubjectFormatter::class;
        $this->assertTrue(class_exists($class));

        $subject = $class::format((object) [
            'matakuliah' => (object) [
                'kode_mk' => 'IF101',
                'nama_mk' => 'Algoritma',
            ],
            'kelas' => (object) [
                'nama_kelas' => 'A',
            ],
        ]);

        $this->assertSame('IF101 - Algoritma / A', $subject);
    }

    public function test_falls_back_to_subject_type_when_label_is_empty(): void
    {
        $class = KelasKuliahSubjectFormatter::class;
        $this->assertTrue(class_exists($class));

        $this->assertSame('Kelas Kuliah', $class::format((object) []));
    }
}
