<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_dan_atur_sandi_bisa_dibuka(): void
    {
        $this->get('/lupa-sandi')->assertOk();
        $this->get('/atur-sandi/token-uji?email=budi@example.com')->assertOk();
    }

    public function test_tautan_atur_ulang_dikirim_untuk_email_terdaftar(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'budi@example.com']);

        $this->post('/lupa-sandi', ['email' => 'budi@example.com'])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_tidak_terdaftar_tetap_mendapat_jawaban_sama(): void
    {
        Notification::fake();

        $this->post('/lupa-sandi', ['email' => 'tidak-ada@example.com'])
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_permintaan_tautan_dibatasi(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/lupa-sandi', ['email' => 'budi@example.com'])
                ->assertSessionHas('success');
        }

        $this->post('/lupa-sandi', ['email' => 'budi@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_kata_sandi_bisa_diatur_ulang_dengan_token_sah(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'lama12345',
        ]);

        $token = Password::createToken($user);

        $this->post('/atur-sandi', [
            'token' => $token,
            'email' => 'budi@example.com',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_token_salah_ditolak(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'lama12345',
        ]);

        $this->post('/atur-sandi', [
            'token' => 'token-palsu',
            'email' => 'budi@example.com',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }

    public function test_validasi_kata_sandi_baru(): void
    {
        $this->post('/atur-sandi', [
            'token' => 'x',
            'email' => 'bukan-email',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['email', 'password']);
    }
}
