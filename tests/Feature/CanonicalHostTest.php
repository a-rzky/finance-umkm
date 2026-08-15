<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_www_dialihkan_permanen_ke_domain_polos(): void
    {
        $this->get('http://www.kaskita.site/masuk')
            ->assertStatus(301)
            ->assertRedirect('http://kaskita.site/masuk');
    }

    public function test_pengalihan_mempertahankan_query_string(): void
    {
        $this->get('http://www.kaskita.site/rekap?from=2026-08-01&until=2026-08-14')
            ->assertRedirect('http://kaskita.site/rekap?from=2026-08-01&until=2026-08-14');
    }

    public function test_permintaan_non_get_memakai_308_agar_metode_tidak_berubah(): void
    {
        // 301 boleh diubah klien menjadi GET; 308 menjamin metode dan badan
        // permintaan ikut terkirim ke host yang benar.
        $this->post('http://www.kaskita.site/masuk', [
            'username' => 'busri',
            'password' => 'rahasia123',
        ])->assertStatus(308);
    }

    public function test_domain_polos_tidak_dialihkan(): void
    {
        $this->get('http://kaskita.site/masuk')->assertStatus(200);
    }

    public function test_host_lain_tidak_terpengaruh(): void
    {
        // Domain bawaan Railway dan localhost harus tetap bisa dipakai.
        $this->get('http://finance-umkm-production.up.railway.app/masuk')->assertStatus(200);
        $this->get('http://localhost/masuk')->assertStatus(200);
    }
}
