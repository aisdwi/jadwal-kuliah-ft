<?php

namespace Tests\Unit\Domain\Penjadwalan\Policy;

use App\Domain\Penjadwalan\Policy\KapasitasRuangPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test: KapasitasRuangPolicy
 *
 * Pure PHP — tidak memerlukan Laravel kernel maupun koneksi database.
 */
class KapasitasRuangPolicyTest extends TestCase
{
    private KapasitasRuangPolicy $policy;

    protected function setUp(): void
    {
        $this->markTestSkipped('Legacy room-capacity policy test; current room allocation coverage is RoomAllocationPolicyTest.');
    }

    // ── isKapasitasCukup ──────────────────────────────────────────────────────

    #[Test]
    public function isKapasitasCukup_returnsTrue_whenCapacityEquals(): void
    {
        self::assertTrue($this->policy->isKapasitasCukup(kapasitasRuangan: 40, jumlahMahasiswa: 40));
    }

    #[Test]
    public function isKapasitasCukup_returnsTrue_whenCapacityGreater(): void
    {
        self::assertTrue($this->policy->isKapasitasCukup(kapasitasRuangan: 40, jumlahMahasiswa: 30));
    }

    #[Test]
    public function isKapasitasCukup_returnsFalse_whenCapacityLess(): void
    {
        self::assertFalse($this->policy->isKapasitasCukup(kapasitasRuangan: 30, jumlahMahasiswa: 40));
    }

    #[Test]
    public function isKapasitasCukup_returnsTrue_whenZeroStudents(): void
    {
        self::assertTrue($this->policy->isKapasitasCukup(kapasitasRuangan: 0, jumlahMahasiswa: 0));
    }

    // ── selisihKelebihan ──────────────────────────────────────────────────────

    #[Test]
    public function selisihKelebihan_returnsZero_whenCapacitySufficient(): void
    {
        self::assertSame(0, $this->policy->selisihKelebihan(kapasitasRuangan: 40, jumlahMahasiswa: 40));
        self::assertSame(0, $this->policy->selisihKelebihan(kapasitasRuangan: 40, jumlahMahasiswa: 30));
    }

    #[Test]
    public function selisihKelebihan_returnsOverflowCount_whenTooManyStudents(): void
    {
        $result = $this->policy->selisihKelebihan(kapasitasRuangan: 30, jumlahMahasiswa: 40);

        self::assertSame(10, $result, 'Kelebihan 10 mahasiswa.');
    }

    #[Test]
    public function selisihKelebihan_returnsOne_whenOneStudentOver(): void
    {
        self::assertSame(1, $this->policy->selisihKelebihan(kapasitasRuangan: 39, jumlahMahasiswa: 40));
    }

    // ── Kombinasi ─────────────────────────────────────────────────────────────

    #[Test]
    public function fullScenario_overCapacity(): void
    {
        $kapasitas = 25;
        $jumlah    = 32;

        self::assertFalse($this->policy->isKapasitasCukup($kapasitas, $jumlah));
        self::assertSame(7, $this->policy->selisihKelebihan($kapasitas, $jumlah));
    }

    #[Test]
    public function fullScenario_exactCapacity(): void
    {
        $kapasitas = 25;
        $jumlah    = 25;

        self::assertTrue($this->policy->isKapasitasCukup($kapasitas, $jumlah));
        self::assertSame(0, $this->policy->selisihKelebihan($kapasitas, $jumlah));
    }
}
