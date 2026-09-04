<?php

namespace Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;

class ScheduleEditorCardDisplayTest extends TestCase
{
    public function test_course_cards_prioritize_course_name_and_keep_code_searchable(): void
    {
        $editor = file_get_contents(__DIR__ . '/../../../resources/js/pages/ScheduleEditor.tsx');
        $cards = file_get_contents(__DIR__ . '/../../../resources/js/pages/schedule-editor/ScheduleCards.tsx');
        $types = file_get_contents(__DIR__ . '/../../../resources/js/pages/schedule-editor/types.ts');
        $query = file_get_contents(__DIR__ . '/../../../app/Modules/Penjadwalan/Infrastructure/Scheduling/Repositories/SchedulingKelasKuliahListQuery.php');

        $this->assertStringContainsString('classContext:', $editor);
        $this->assertStringContainsString('item.kelas?.nama_kelas', $editor);
        $this->assertStringContainsString('item.kelas?.program_studi?.nama_prodi', $editor);
        $this->assertStringContainsString('${c.code || ""} ${c.name || ""}', $editor);

        $this->assertStringContainsString('classContext?: string;', $types);
        $this->assertStringContainsString('{course.classContext}', $cards);
        $this->assertStringContainsString('{entry.course.classContext}', $cards);
        $this->assertStringContainsString('style={{ color: `hsl(${course.color})` }}', $cards);
        $this->assertStringContainsString('style={{ color: `hsl(${entry.course.color})` }}', $cards);
        $this->assertStringContainsString('whitespace-normal break-words', $cards);
        $this->assertStringContainsString('WebkitLineClamp: 2', $cards);
        $this->assertStringNotContainsString('{entry.course.code || entry.course.name}', $cards);
        $this->assertStringContainsString("'kelas.programStudi'", $query);
    }
}
