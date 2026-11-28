<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Masuk dan daftar dengan email dan kata sandi.
 *
 * Login Google tetap ada dan tetap cara tercepat. Jalur ini untuk
 * pengguna yang tidak punya akun Google, atau yang lebih suka kata sandi.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Isi email dulu.',
            'email.email' => 'Alamat email belum benar.',
            'password.required' => 'Isi kata sandi dulu.',
        ]);

        $this->ensureNotThrottled($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $user = $request->user();
        $user->update(['last_login_at' => now()]);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return $user->isOnboarded()
            ? redirect()->intended(route('dashboard'))
            : redirect()->route('onboarding.show');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Isi namamu dulu.',
            'name.min' => 'Nama paling pendek 2 huruf.',
            'email.required' => 'Isi email dulu.',
            'email.email' => 'Alamat email belum benar, contoh: nama@email.com.',
            'email.unique' => 'Email ini sudah terdaftar. Coba masuk saja.',
            'email.lowercase' => 'Huruf pada email harus kecil semua.',
            'password.required' => 'Buat kata sandi dulu.',
            'password.min' => 'Kata sandi paling pendek 8 huruf.',
            'password.confirmed' => 'Kata sandi dan ulangannya belum sama.',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'role' => $this->roleFor($request->input('email')),
            'last_login_at' => now(),
        ]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Akun admin dibuat.');
        }

        return redirect()
            ->route('onboarding.show')
            ->with('success', 'Akun dibuat. Sekarang lengkapi profilmu.');
    }

    /**
     * Email admin dari .env otomatis mendapat peran admin.
     */
    private function roleFor(string $email): Role
    {
        $adminEmail = config('gizisnap.admin_email');

        return $adminEmail && strcasecmp($adminEmail, $email) === 0
            ? Role::Admin
            : Role::User;
    }

    /**
     * Batasi percobaan masuk: 5 kali per menit per email dan IP.
     */
    private function ensureNotThrottled(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
