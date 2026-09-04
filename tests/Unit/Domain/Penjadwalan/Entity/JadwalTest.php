<?php

namespace Tests\Unit\Domain\Penjadwalan\Entity;

use App\Domain\Penjadwalan\Entity\Jadwal;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test: Jadwal Entity Invariants
 *
 * Membuktikan bahwa entitas Domain menerapkan invariant bisnisnya sendiri
 * tanpa perlu database atau framework.
 *
 * Referensi: Robert C. Martin, Clean Architecture (2018) Ch. 20 hal. 189–194
 * — "Entities encapsulate Enterprise-wide Critical Business Rules."
 */
class JadwalTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Legacy Jadwal entity test; current scheduling domain uses ScheduleAssignment and JadwalModel.');
    }

    // ── Valid construction ────────────────────────────────────────────────────

    #[Test]
    public function canBeCreated_withValidMinimalArgs(): void
    {
        $jadwal = new Jadwal(
            id:            null,
            kelasKuliahId: 1,
            slotId:        5,
            ruanganId:     3,
            origin:        Jadwal::ORIGIN_MANUAL,
        );

        self::assertNull($jadwal->id);
        self::assertSame(1, $jadwal->kelasKuliahId);
        self::assertSame(5, $jadwal->slotId);
        self::assertSame(3, $jadwal->ruanganId);
        self::assertSame(Jadwal::ORIGIN_MANUAL, $jadwal->origin);
        self::assertTrue($jadwal->isManual());
    }

    #[Test]
    public function canBeCreated_withOriginGenerated(): void
    {
        $jadwal = new Jadwal(
            id:            10,
            kelasKuliahId: 2,
            slotId:        8,
            ruanganId:     4,
            origin:        Jadwal::ORIGIN_GENERATED,
        );

        self::assertFalse($jadwal->isManual());
        self::assertSame(Jadwal::ORIGIN_GENERATED, $jadwal->origin);
    }

    // ── Invariant: kelasKuliahId >= 1 ─────────────────────────────────────────

    #[Test]
    public function throws_whenKelasKuliahIdIsZero(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/KelasKuliah wajib/');

        new Jadwal(id: null, kelasKuliahId: 0, slotId: 1, ruanganId: 1);
    }

    #[Test]
    public function throws_whenKelasKuliahIdIsNegative(): void
    {
        $this->expectException(DomainException::class);

        new Jadwal(id: null, kelasKuliahId: -5, slotId: 1, ruanganId: 1);
    }

    // ── Invariant: slotId >= 1 ────────────────────────────────────────────────

    #[Test]
    public function throws_whenSlotIdIsZero(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Slot wajib/');

        new Jadwal(id: null, kelasKuliahId: 1, slotId: 0, ruanganId: 1);
    }

    // ── Invariant: ruanganId >= 1 ─────────────────────────────────────────────

    #[Test]
    public function throws_whenRuanganIdIsZero(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Ruangan wajib/');

        new Jadwal(id: null, kelasKuliahId: 1, slotId: 1, ruanganId: 0);
    }

    // ── Invariant: origin ─────────────────────────────────────────────────────

    #[Test]
    public function throws_whenOriginIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Origin jadwal tidak valid/');

        new Jadwal(id: null, kelasKuliahId: 1, slotId: 1, ruanganId: 1, origin: 'invalid');
    }

    // ── Immutability ──────────────────────────────────────────────────────────

    #[Test]
    public function isImmutable_afterConstruction(): void
    {
        $jadwal = new Jadwal(id: 1, kelasKuliahId: 1, slotId: 1, ruanganId: 1);

        // PHP readonly property: harus throw Error jika dimodifikasi (PHPUnit 11+)
        $this->expectException(\Error::class);

        /** @phpstan-ignore-next-line */
        $jadwal->slotId = 99;
    }
}
