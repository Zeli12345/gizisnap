<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_dan_daftar_bisa_dibuka(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Masuk');
        $this->get('/daftar')->assertOk()->assertSee('Daftar');
    }

    public function test_tamu_dialihkan_ke_halaman_masuk(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/scan')->assertRedirect(route('login'));
    }

    public function test_pengguna_bisa_mendaftar_lalu_diarahkan_ke_onboarding(): void
    {
        $response = $this->post('/daftar', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('onboarding.show'));
        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'budi@example.com');
        $this->assertNotNull($user);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame(Role::User, $user->role);
        $this->assertNull($user->onboarded_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_email_admin_mendapat_peran_admin_saat_daftar(): void
    {
        config(['gizisnap.admin_email' => 'admin@gizisnap.test']);

        $this->post('/daftar', [
            'name' => 'Admin Gizi',
            'email' => 'admin@gizisnap.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertSame(Role::Admin, User::firstWhere('email', 'admin@gizisnap.test')->role);
    }

    public function test_validasi_pendaftaran(): void
    {
        User::factory()->create(['email' => 'sudah@example.com']);

        $this->post('/daftar', [
            'name' => 'A',
            'email' => 'Sudah@Example.com',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->post('/daftar', [
            'name' => 'Budi',
            'email' => 'sudah@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Coba masuk saja.']);
    }

    public function test_masuk_mengarahkan_sesuai_status_pengguna(): void
    {
        $onboarded = $this->onboardedUser(['email' => 'siap@example.com']);

        $this->post('/masuk', [
            'email' => 'siap@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($onboarded->fresh());
        $this->assertNotNull($onboarded->fresh()->last_login_at);

        $this->post('/logout');

        $belum = User::factory()->create(['email' => 'baru@example.com']);

        $this->post('/masuk', [
            'email' => 'baru@example.com',
            'password' => 'password',
        ])->assertRedirect(route('onboarding.show'));

        $this->assertAuthenticatedAs($belum);

        $this->post('/logout');

        $admin = $this->adminUser(['email' => 'admin@example.com']);

        $this->post('/masuk', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->post('/masuk', [
            'email' => 'budi@example.com',
            'password' => 'salah',
        ])->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

        $this->assertGuest();
    }

    public function test_percobaan_masuk_dibatasi(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', [
                'email' => 'budi@example.com',
                'password' => 'salah',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/masuk', [
            'email' => 'budi@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('email')
        );
    }

    public function test_keluar_mengembalikan_ke_beranda(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }
}
