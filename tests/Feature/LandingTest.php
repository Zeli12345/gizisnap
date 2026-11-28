<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_tampil_untuk_tamu(): void
    {
        $this->makeFood(['name' => 'Nasi putih']);
        $this->makeFood(['name' => 'Ayam bakar']);
        $this->makeFood(['name' => 'Tidak aktif', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('GiziSnap')
            ->assertSee('Nasi putih')
            ->assertDontSee('Tidak aktif');
    }

    public function test_beranda_tanpa_data_makanan_tetap_tampil(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_pengguna_masuk_dialihkan_ke_dashboard(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_halaman_privasi_dan_health_check(): void
    {
        $this->get('/privasi')->assertOk();
        $this->get('/up')->assertOk();
    }
}
