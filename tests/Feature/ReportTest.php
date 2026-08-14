<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\ReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $sri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sri = $this->registerBusiness('Warung Bu Sri', 'busri');
        $this->actingAs($this->sri);
    }

    private function catat(string $type, int $amount, string $date, ?int $categoryId = null): void
    {
        app(TransactionService::class)->record([
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'occurred_on' => $date,
            'note' => null,
        ], $this->sri);
    }

    public function test_rekap_dikelompokkan_per_hari(): void
    {
        $hariIni = today()->toDateString();
        $kemarin = today()->subDay()->toDateString();

        $this->catat('masuk', 100_000, $kemarin);
        $this->catat('keluar', 40_000, $kemarin);
        $this->catat('masuk', 25_000, $hariIni);

        $rekap = app(ReportService::class)->daily($kemarin, $hariIni);

        $this->assertCount(2, $rekap);

        // Diurutkan dari yang terbaru.
        $this->assertSame(
            ['date' => $hariIni, 'masuk' => 25_000, 'keluar' => 0, 'saldo' => 25_000],
            $rekap->first()
        );
        $this->assertSame(
            ['date' => $kemarin, 'masuk' => 100_000, 'keluar' => 40_000, 'saldo' => 60_000],
            $rekap->last()
        );
    }

    public function test_hari_tanpa_transaksi_tidak_muncul(): void
    {
        $this->catat('masuk', 10_000, today()->toDateString());

        $rekap = app(ReportService::class)->daily(
            today()->subDays(6)->toDateString(),
            today()->toDateString()
        );

        $this->assertCount(1, $rekap);
    }

    public function test_transaksi_di_luar_rentang_tidak_dihitung(): void
    {
        $this->catat('masuk', 999_000, today()->subDays(10)->toDateString());
        $this->catat('masuk', 10_000, today()->toDateString());

        $rekap = app(ReportService::class)->daily(
            today()->subDays(6)->toDateString(),
            today()->toDateString()
        );

        $this->assertSame(10_000, $rekap->sum('masuk'));
    }

    public function test_rincian_per_kategori(): void
    {
        $hariIni = today()->toDateString();
        $belanja = Category::firstWhere('name', 'Belanja Stok');
        $gaji = Category::firstWhere('name', 'Gaji');

        $this->catat('keluar', 40_000, $hariIni, $belanja->id);
        $this->catat('keluar', 15_000, $hariIni, $belanja->id);
        $this->catat('keluar', 60_000, $hariIni, $gaji->id);
        $this->catat('keluar', 5_000, $hariIni);

        $rincian = app(ReportService::class)->byCategory($hariIni, $hariIni);

        // Diurutkan dari nominal terbesar.
        $this->assertSame('Gaji', $rincian->first()['category']);
        $this->assertSame(60_000, $rincian->first()['total']);

        $belanjaStok = $rincian->firstWhere('category', 'Belanja Stok');
        $this->assertSame(55_000, $belanjaStok['total']);
        $this->assertSame('keluar', $belanjaStok['type']);

        // Transaksi tanpa kategori tetap terhitung, tidak hilang diam-diam.
        $this->assertSame(5_000, $rincian->firstWhere('category', 'Tanpa kategori')['total']);
    }

    public function test_halaman_rekap_memakai_rentang_tujuh_hari_secara_bawaan(): void
    {
        $this->get('/rekap')->assertInertia(
            fn ($page) => $page
                ->component('Rekap')
                ->where('from', today()->subDays(6)->toDateString())
                ->where('until', today()->toDateString())
        );
    }

    public function test_rentang_terbalik_ditolak(): void
    {
        $this->get('/rekap?from='.today()->toDateString().'&until='.today()->subDays(5)->toDateString())
            ->assertSessionHasErrors('until');
    }

    public function test_rentang_lebih_dari_setahun_ditolak(): void
    {
        $this->get('/rekap?from='.today()->subYears(3)->toDateString().'&until='.today()->toDateString())
            ->assertSessionHasErrors('until');
    }
}
