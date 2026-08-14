<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect('/masuk');
        $this->get('/riwayat')->assertRedirect('/masuk');
        $this->get('/rekap')->assertRedirect('/masuk');
    }

    public function test_pendaftaran_membuat_usaha_beserta_kategori_bawaan(): void
    {
        $response = $this->post('/daftar', [
            'business_name' => 'Warung Bu Sri',
            'name' => 'Sri Wahyuni',
            'username' => 'busri',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::firstWhere('username', 'busri');
        $this->assertNotNull($user);
        $this->assertSame('Warung Bu Sri', Tenant::find($user->tenant_id)->name);

        $this->assertSame(
            9,
            Category::withoutGlobalScopes()->where('tenant_id', $user->tenant_id)->count()
        );
    }

    public function test_nama_pengguna_disimpan_dalam_huruf_kecil(): void
    {
        $this->post('/daftar', [
            'business_name' => 'Warung Bu Sri',
            'name' => 'Sri Wahyuni',
            'username' => 'BuSri',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'busri']);
    }

    public function test_nama_pengguna_tidak_boleh_kembar(): void
    {
        $this->registerBusiness('Warung A', 'busri');

        $this->post('/daftar', [
            'business_name' => 'Warung B',
            'name' => 'Budi',
            'username' => 'busri',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('username');

        $this->assertSame(1, User::count());
    }

    public function test_bisa_masuk_dengan_nama_pengguna_huruf_besar(): void
    {
        $this->registerBusiness('Warung A', 'busri');

        $this->post('/masuk', [
            'username' => 'BUSRI',
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        $this->registerBusiness('Warung A', 'busri');

        $this->post('/masuk', [
            'username' => 'busri',
            'password' => 'salah',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_percobaan_masuk_dibatasi_setelah_lima_kali_gagal(): void
    {
        $this->registerBusiness('Warung A', 'busri');

        foreach (range(1, 5) as $ignored) {
            $this->post('/masuk', ['username' => 'busri', 'password' => 'salah']);
        }

        $response = $this->post('/masuk', ['username' => 'busri', 'password' => 'rahasia123']);

        // Kata sandi benar pun harus ditolak selama masih terkunci.
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_keluar_mengakhiri_sesi(): void
    {
        $user = $this->registerBusiness('Warung A', 'busri');

        $this->actingAs($user)->post('/keluar')->assertRedirect('/masuk');

        $this->assertGuest();
    }
}
