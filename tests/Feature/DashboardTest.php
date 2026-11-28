<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_belum_onboarding_dialihkan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_admin_dialihkan_ke_panel_admin(): void
    {
        $this->actingAs($this->adminUser())
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_tampil_dan_kunjungan_harian_dicatat_sekali(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['title' => 'Nasi putih']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertSame(1, UserVisit::where('user_id', $user->id)->count());
    }

    public function test_tanggal_lampau_dan_tanggal_tidak_valid_tetap_tampil(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['eaten_at' => now()->subDays(2)]);

        $this->actingAs($user)
            ->get('/dashboard?date='.now()->subDays(2)->toDateString())
            ->assertOk();

        $this->actingAs($user)
            ->get('/dashboard?date=bukan-tanggal')
            ->assertOk();
    }

    public function test_tanggal_masa_depan_diarahkan_ke_hari_ini(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->get('/dashboard?date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertViewHas('date', fn ($date) => $date->isToday());
    }

    public function test_parameter_welcome_diteruskan_ke_tampilan(): void
    {
        $this->actingAs($this->onboardedUser())
            ->get('/dashboard?welcome=1')
            ->assertOk()
            ->assertViewHas('welcome', true);
    }
}
