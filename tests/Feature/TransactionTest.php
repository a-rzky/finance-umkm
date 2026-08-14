<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $sri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sri = $this->registerBusiness('Warung Bu Sri', 'busri');
        $this->actingAs($this->sri);
    }

    private function kirim(array $ubah = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/transaksi', array_merge([
            'type' => 'masuk',
            'amount' => 25_000,
            'occurred_on' => today()->toDateString(),
        ], $ubah));
    }

    public function test_bisa_mencatat_uang_masuk(): void
    {
        $this->kirim(['note' => 'Jual gorengan'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'type' => 'masuk',
            'amount' => 25_000,
            'note' => 'Jual gorengan',
            'tenant_id' => $this->sri->tenant_id,
            'user_id' => $this->sri->id,
        ]);
    }

    public function test_bisa_mencatat_uang_keluar_dengan_kategori(): void
    {
        $kategori = Category::firstWhere('name', 'Belanja Stok');

        $this->kirim(['type' => 'keluar', 'amount' => 40_000, 'category_id' => $kategori->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'type' => 'keluar',
            'amount' => 40_000,
            'category_id' => $kategori->id,
        ]);
    }

    public function test_nominal_nol_ditolak(): void
    {
        $this->kirim(['amount' => 0])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_nominal_negatif_ditolak(): void
    {
        $this->kirim(['amount' => -5_000])->assertSessionHasErrors('amount');
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $this->kirim(['occurred_on' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('occurred_on');
    }

    public function test_kategori_harus_sesuai_jenis_transaksi(): void
    {
        // "Belanja Stok" adalah kategori pengeluaran, tidak sah untuk uang masuk.
        $kategoriKeluar = Category::firstWhere('name', 'Belanja Stok');

        $this->kirim(['type' => 'masuk', 'category_id' => $kategoriKeluar->id])
            ->assertSessionHasErrors('category_id');
    }

    public function test_bisa_mengubah_transaksi_sendiri(): void
    {
        $this->kirim();
        $id = \App\Models\Transaction::first()->id;

        $this->put("/transaksi/{$id}", [
            'type' => 'masuk',
            'amount' => 30_000,
            'occurred_on' => today()->toDateString(),
            'note' => 'diperbaiki',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', ['id' => $id, 'amount' => 30_000, 'note' => 'diperbaiki']);
    }

    public function test_bisa_menghapus_transaksi_sendiri(): void
    {
        $this->kirim();
        $id = \App\Models\Transaction::first()->id;

        $this->delete("/transaksi/{$id}")->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('transactions', ['id' => $id]);
    }

    public function test_layar_kasir_menampilkan_ringkasan_hari_ini(): void
    {
        $this->kirim(['type' => 'masuk', 'amount' => 25_000]);
        $this->kirim(['type' => 'keluar', 'amount' => 10_000]);

        $this->get('/')->assertInertia(
            fn ($page) => $page
                ->component('Kasir')
                ->where('today.masuk', 25_000)
                ->where('today.keluar', 10_000)
                ->where('today.saldo', 15_000)
        );
    }
}
