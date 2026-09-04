<?php

namespace Tests\Unit\Domain\Penjadwalan\Policy;

use App\Domain\Penjadwalan\Entity\Jadwal;
use App\Domain\Penjadwalan\Policy\BentrokRuangPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test: BentrokRuangPolicy
 *
 * Pure PHP — tidak memerlukan Laravel kernel maupun koneksi database.
 */
class BentrokRuangPolicyTest extends TestCase
{
    private BentrokRuangPolicy $policy;

    protected function setUp(): void
    {
        $this->markTestSkipped('Legacy conflict policy test; current coverage is ScheduleConflictCheckerTest.');
    }

    private function jadwal(int $id, int $kelasKuliahId, int $slotId, int $ruanganId): Jadwal
    {
        return new Jadwal(
            id:            $id,
            kelasKuliahId: $kelasKuliahId,
            slotId:        $slotId,
            ruanganId:     $ruanganId,
            origin:        Jadwal::ORIGIN_GENERATED,
        );
    }

    // ── isBentrok ─────────────────────────────────────────────────────────────

    #[Test]
    public function isBentrok_returnsFalse_whenEmpty(): void
    {
        self::assertFalse($this->policy->isBentrok([], ruanganId: 1, slotId: 3));
    }

    #[Test]
    public function isBentrok_returnsFalse_whenDifferentSlot(): void
    {
        $existing = [$this->jadwal(1, 1, slotId: 1, ruanganId: 5)];

        self::assertFalse($this->policy->isBentrok($existing, ruanganId: 5, slotId: 99));
    }

    #[Test]
    public function isBentrok_returnsFalse_whenDifferentRuangan(): void
    {
        $existing = [$this->jadwal(1, 1, slotId: 3, ruanganId: 5)];

        self::assertFalse($this->policy->isBentrok($existing, ruanganId: 99, slotId: 3));
    }

    #[Test]
    public function isBentrok_returnsTrue_whenSameRuanganSameSlot(): void
    {
        $existing = [$this->jadwal(1, 1, slotId: 3, ruanganId: 5)];

        self::assertTrue($this->policy->isBentrok($existing, ruanganId: 5, slotId: 3));
    }

    #[Test]
    public function isBentrok_detectsAmongMultiple_whenOneMatches(): void
    {
        $existing = [
            $this->jadwal(1, 1, slotId: 1, ruanganId: 5),
            $this->jadwal(2, 2, slotId: 3, ruanganId: 5),  // bentrok!
            $this->jadwal(3, 3, slotId: 5, ruanganId: 5),
        ];

        self::assertTrue($this->policy->isBentrok($existing, ruanganId: 5, slotId: 3));
        self::assertFalse($this->policy->isBentrok($existing, ruanganId: 5, slotId: 99));
    }

    // ── findAllBentrok ────────────────────────────────────────────────────────

    #[Test]
    public function findAllBentrok_returnsEmpty_whenNoBentrok(): void
    {
        $jadwals = [
            $this->jadwal(1, 1, slotId: 1, ruanganId: 5),
            $this->jadwal(2, 2, slotId: 2, ruanganId: 5),  // Ruangan sama, slot berbeda
            $this->jadwal(3, 3, slotId: 1, ruanganId: 6),  // Slot sama, ruangan berbeda
        ];

        self::assertEmpty($this->policy->findAllBentrok($jadwals));
    }

    #[Test]
    public function findAllBentrok_returnsConflict_whenRuanganDuplicateSlot(): void
    {
        $jadwals = [
            $this->jadwal(1, kelasKuliahId: 1, slotId: 3, ruanganId: 5),
            $this->jadwal(2, kelasKuliahId: 2, slotId: 3, ruanganId: 5),  // bentrok!
        ];

        $result = $this->policy->findAllBentrok($jadwals);

        self::assertArrayHasKey('5_3', $result, 'Key bentrok harus ruanganId_slotId.');
        self::assertCount(2, $result['5_3'], 'Dua kelas_kuliah_id terlibat.');
        self::assertContains(1, $result['5_3']);
        self::assertContains(2, $result['5_3']);
    }

    #[Test]
    public function findAllBentrok_handlesMultipleConflicts(): void
    {
        $jadwals = [
            $this->jadwal(1, 1, slotId: 3, ruanganId: 5),
            $this->jadwal(2, 2, slotId: 3, ruanganId: 5),   // bentrok A
            $this->jadwal(3, 3, slotId: 7, ruanganId: 9),
            $this->jadwal(4, 4, slotId: 7, ruanganId: 9),   // bentrok B
            $this->jadwal(5, 5, slotId: 3, ruanganId: 99),  // tidak bentrok (ruangan lain)
        ];

        $result = $this->policy->findAllBentrok($jadwals);

        self::assertCount(2, $result, 'Tepat 2 pasang bentrok.');
        self::assertArrayHasKey('5_3', $result);
        self::assertArrayHasKey('9_7', $result);
    }
}
