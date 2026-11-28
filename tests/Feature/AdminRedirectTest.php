<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => Role::Admin,
        ]);
    }

    public function test_admin_langsung_ke_panel_admin_tanpa_onboarding(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($admin)->get('/onboarding')->assertRedirect('/admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_data_diri_tidak_tampil_di_akun_admin(): void
    {
        $this->actingAs($this->admin())->get('/profile')
            ->assertOk()
            ->assertDontSee('Data diri')
            ->assertSee('Akun admin');
    }
}
