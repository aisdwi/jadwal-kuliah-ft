<?php

namespace Tests\Unit\Domain\Penjadwalan\Policy;

use App\Domain\Penjadwalan\Entity\Jadwal;
use App\Domain\Penjadwalan\Policy\BentrokDosenPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test: BentrokDosenPolicy
 *
 * Dijalankan tanpa Laravel kernel — pure PHP.
 * Membuktikan ISO/IEC 25010 Testability (§4.5.5): domain business rule
 * dapat diverifikasi independen dari framework dan database.
 *
 * Referensi: Robert C. Martin, Clean Architecture (2018) Ch. 20 — Business Rules.
 */
class BentrokDosenPolicyTest extends TestCase
{
    private BentrokDosenPolicy $policy;

    protected function setUp(): void
    {
        $this->markTestSkipped('Legacy conflict policy test; current coverage is ScheduleConflictCheckerTest.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Buat Jadwal minimal untuk keperluan test. */
    private function jadwal(int $id, int $kelasKuliahId, int $slotId, int $ruanganId, ?int $dosenId): Jadwal
    {
        return new Jadwal(
            id:            $id,
            kelasKuliahId: $kelasKuliahId,
            slotId:        $slotId,
            ruanganId:     $ruanganId,
            origin:        Jadwal::ORIGIN_GENERATED,
            dosenId:       $dosenId,
        );
    }

    // ── isBentrok ─────────────────────────────────────────────────────────────

    #[Test]
    public function isBentrok_returnsFalse_whenArrayIsEmpty(): void
    {
        $result = $this->policy->isBentrok([], dosenId: 1, slotId: 5);

        self::assertFalse($result);
    }

    #[Test]
    public function isBentrok_returnsFalse_whenDosenDifferentSlot(): void
    {
        $existing = [
            $this->jadwal(id: 10, kelasKuliahId: 1, slotId: 1, ruanganId: 1, dosenId: 1),
        ];

        $result = $this->policy->isBentrok($existing, dosenId: 1, slotId: 99);

        self::assertFalse($result, 'Slot berbeda = tidak bentrok.');
    }

    #[Test]
    public function isBentrok_returnsFalse_whenSameSlotDifferentDosen(): void
    {
        $existing = [
            $this->jadwal(id: 10, kelasKuliahId: 1, slotId: 5, ruanganId: 1, dosenId: 1),
        ];

        $result = $this->policy->isBentrok($existing, dosenId: 99, slotId: 5);

        self::assertFalse($result, 'Dosen berbeda = tidak bentrok.');
    }

    #[Test]
    public function isBentrok_returnsTrue_whenSameDosenSameSlot(): void
    {
        $existing = [
            $this->jadwal(id: 10, kelasKuliahId: 1, slotId: 5, ruanganId: 1, dosenId: 7),
        ];

        $result = $this->policy->isBentrok($existing, dosenId: 7, slotId: 5);

        self::assertTrue($result, 'Dosen & slot sama = bentrok!');
    }

    #[Test]
    public function isBentrok_ignoresJadwal_withNullDosenId(): void
    {
        // Jadwal tanpa dosen (TBA) tidak boleh memicu bentrok
        $existing = [
            $this->jadwal(id: 10, kelasKuliahId: 1, slotId: 5, ruanganId: 1, dosenId: null),
        ];

        $result = $this->policy->isBentrok($existing, dosenId: 7, slotId: 5);

        self::assertFalse($result, 'Dosen null = tidak dihitung sebagai konflik.');
    }

    #[Test]
    public function isBentrok_detectsAmongMultiple_whenOneMatches(): void
    {
        $existing = [
            $this->jadwal(id: 1, kelasKuliahId: 1, slotId: 2, ruanganId: 1, dosenId: 7),
            $this->jadwal(id: 2, kelasKuliahId: 2, slotId: 5, ruanganId: 2, dosenId: 7),  // bentrok
            $this->jadwal(id: 3, kelasKuliahId: 3, slotId: 8, ruanganId: 3, dosenId: 7),
        ];

        self::assertTrue($this->policy->isBentrok($existing, dosenId: 7, slotId: 5));
        self::assertFalse($this->policy->isBentrok($existing, dosenId: 7, slotId: 99));
    }

    // ── findAllBentrok ────────────────────────────────────────────────────────

    #[Test]
    public function findAllBentrok_returnsEmpty_whenNoBentrok(): void
    {
        $jadwals = [
            $this->jadwal(1, 1, 1, 1, dosenId: 1),
            $this->jadwal(2, 2, 2, 2, dosenId: 1),  // Dosen 1, slot berbeda
            $this->jadwal(3, 3, 1, 3, dosenId: 2),  // Dosen berbeda, slot sama
        ];

        $result = $this->policy->findAllBentrok($jadwals);

        self::assertEmpty($result, 'Tidak ada dosen yang mengajar dua kelas di slot yang sama.');
    }

    #[Test]
    public function findAllBentrok_returnsConflict_whenDosenDuplicateSlot(): void
    {
        $jadwals = [
            $this->jadwal(1, kelasKuliahId: 1, slotId: 5, ruanganId: 1, dosenId: 7),
            $this->jadwal(2, kelasKuliahId: 2, slotId: 5, ruanganId: 2, dosenId: 7),  // bentrok!
            $this->jadwal(3, kelasKuliahId: 3, slotId: 8, ruanganId: 3, dosenId: 7),
        ];

        $result = $this->policy->findAllBentrok($jadwals);

        self::assertArrayHasKey(7, $result, 'Dosen 7 harus masuk laporan bentrok.');
        self::assertContains(5, $result[7], 'Slot 5 adalah slot yang bentrok.');
        self::assertNotContains(8, $result[7], 'Slot 8 tidak bentrok.');
    }

    #[Test]
    public function findAllBentrok_skipsJadwal_withNullDosenId(): void
    {
        $jadwals = [
            $this->jadwal(1, kelasKuliahId: 1, slotId: 5, ruanganId: 1, dosenId: null),
            $this->jadwal(2, kelasKuliahId: 2, slotId: 5, ruanganId: 2, dosenId: null),
        ];

        $result = $this->policy->findAllBentrok($jadwals);

        self::assertEmpty($result, 'Jadwal tanpa dosen tidak dihitung sebagai bentrok.');
    }
}
