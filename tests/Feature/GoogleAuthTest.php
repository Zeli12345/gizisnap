<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_google_belum_dikonfigurasi(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('error');
    }

    public function test_redirect_google_memakai_url_oauth(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        Socialite::fake('google');

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_callback_membuat_pengguna_baru(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'avatar' => 'https://example.com/siti.jpg',
        ]));

        $this->get('/auth/google/callback')->assertRedirect(route('onboarding.show'));

        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'siti@example.com');
        $this->assertNotNull($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertSame('Siti Aminah', $user->name);
        $this->assertSame(Role::User, $user->role);
    }

    public function test_callback_menggunakan_akun_lama_dan_mengarahkan_pengguna_onboarded(): void
    {
        $user = $this->onboardedUser(['email' => 'siti@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
        ]));

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('g-123', $user->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'siti@example.com')->count());
    }

    public function test_email_admin_otomatis_jadi_admin_lewat_google(): void
    {
        config(['gizisnap.admin_email' => 'admin@gizisnap.test']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-admin',
            'name' => 'Admin',
            'email' => 'admin@gizisnap.test',
        ]));

        $this->get('/auth/google/callback')->assertRedirect(route('admin.dashboard'));

        $this->assertSame(Role::Admin, User::firstWhere('email', 'admin@gizisnap.test')->role);
    }

    public function test_callback_dibatalkan_atau_gagal(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('error', 'Login dibatalkan.');

        Socialite::fake('google', fn () => throw new \RuntimeException('gagal'));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('error', 'Login gagal, coba lagi.');
    }
}
