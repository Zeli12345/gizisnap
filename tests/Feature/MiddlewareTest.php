<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_belum_onboarding_dapat_409_untuk_permintaan_json(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/dashboard')
            ->assertStatus(409)
            ->assertJson(['message' => 'Lengkapi profil terlebih dahulu.']);
    }

    public function test_pengguna_belum_onboarding_dialihkan_untuk_halaman_biasa(): void
    {
        foreach (['/dashboard', '/meals', '/chat', '/reports', '/profile', '/weight'] as $url) {
            $this->actingAs(User::factory()->create())
                ->get($url)
                ->assertRedirect(route('onboarding.show'));
        }
    }

    public function test_admin_tidak_melewati_onboarding_dan_tetap_bisa_panel(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/meals')->assertOk();
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_onboarding_lagi(): void
    {
        $this->actingAs($this->onboardedUser())
            ->get('/onboarding')
            ->assertRedirect(route('dashboard'));
    }
}
