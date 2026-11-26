<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Lupa kata sandi lewat tautan email.
 *
 * Tanpa konfigurasi SMTP, tautannya tetap ditulis ke log Laravel supaya
 * tim bisa mencobanya di laptop (MAIL_MAILER=log).
 */
class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Isi email dulu.',
            'email.email' => 'Alamat email belum benar.',
        ]);

        $key = 'reset|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan. Tunggu satu menit lalu coba lagi.',
            ]);
        }

        RateLimiter::hit($key, 60);

        Password::sendResetLink($request->only('email'));

        // Jawaban selalu sama, supaya orang tidak bisa menebak email mana yang terdaftar.
        return back()->with('success', 'Kalau email itu terdaftar, tautan atur ulang sudah dikirim.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi paling pendek 8 huruf.',
            'password.confirmed' => 'Kata sandi dan ulangannya belum sama.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Tautan atur ulang sudah kedaluwarsa. Minta tautan baru.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Kata sandi sudah diganti. Silakan masuk.');
    }
}
