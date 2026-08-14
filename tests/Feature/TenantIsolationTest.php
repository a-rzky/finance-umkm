<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $sri;

    private User $budi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sri = $this->registerBusiness('Warung Bu Sri', 'busri');
        $this->budi = $this->registerBusiness('Warung Pak Budi', 'pakbudi');
    }

    private function transaksiMilik(User $user, int $amount = 50_000): Transaction
    {
        $this->actingAs($user);

        return app(TransactionService::class)->record([
            'category_id' => null,
            'type' => 'masuk',
            'amount' => $amount,
            'occurred_on' => today()->toDateString(),
            'note' => null,
        ], $user);
    }

    public function test_transaksi_tenant_lain_tidak_terlihat(): void
    {
        $this->transaksiMilik($this->sri, 50_000);
        $this->transaksiMilik($this->budi, 90_000);

        $this->actingAs($this->sri);

        $this->assertSame(1, Transaction::count());
        $this->assertSame(50_000, (int) Transaction::sum('amount'));
    }

    public function test_transaksi_tenant_lain_tidak_bisa_diambil_lewat_id(): void
    {
        $milikBudi = $this->transaksiMilik($this->budi);

        $this->actingAs($this->sri);

        $this->assertNull(Transaction::find($milikBudi->id));
    }

    public function test_tidak_bisa_menghapus_transaksi_tenant_lain(): void
    {
        $milikBudi = $this->transaksiMilik($this->budi);

        $this->actingAs($this->sri)
            ->delete("/transaksi/{$milikBudi->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $milikBudi->id]);
    }

    public function test_tidak_bisa_mengubah_transaksi_tenant_lain(): void
    {
        $milikBudi = $this->transaksiMilik($this->budi, 90_000);

        $this->actingAs($this->sri)
            ->put("/transaksi/{$milikBudi->id}", [
                'type' => 'masuk',
                'amount' => 1,
                'occurred_on' => today()->toDateString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $milikBudi->id, 'amount' => 90_000]);
    }

    public function test_kategori_milik_tenant_lain_ditolak(): void
    {
        $kategoriBudi = Category::withoutGlobalScopes()
            ->where('tenant_id', $this->budi->tenant_id)
            ->firstWhere('name', 'Penjualan');

        $this->actingAs($this->sri)
            ->post('/transaksi', [
                'type' => 'masuk',
                'amount' => 10_000,
                'occurred_on' => today()->toDateString(),
                'category_id' => $kategoriBudi->id,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_tenant_id_dan_user_id_dari_request_diabaikan(): void
    {
        $this->actingAs($this->sri)
            ->post('/transaksi', [
                'type' => 'masuk',
                'amount' => 7_777,
                'occurred_on' => today()->toDateString(),
                // Percobaan menitipkan transaksi ke tenant lain lewat body request.
                'tenant_id' => $this->budi->tenant_id,
                'user_id' => $this->budi->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'amount' => 7_777,
            'tenant_id' => $this->sri->tenant_id,
            'user_id' => $this->sri->id,
        ]);
    }

    public function test_tanpa_login_query_tidak_mengembalikan_apa_pun(): void
    {
        $this->transaksiMilik($this->sri);
        $this->transaksiMilik($this->budi);

        auth()->logout();

        // Fail-closed: route yang lupa dipasangi middleware auth tetap tidak bocor.
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, Category::count());
    }
}
