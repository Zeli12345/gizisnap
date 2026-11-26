<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect()
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('landing')
                ->with('error', 'Login Google belum disiapkan. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di .env.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('landing')->with('error', 'Login dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Login Google gagal', ['error' => $e->getMessage()]);

            return redirect()->route('landing')->with('error', 'Login gagal, coba lagi.');
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first()
            ?? new User;

        $user->fill([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna GiziSnap',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            'last_login_at' => now(),
        ]);

        $adminEmail = config('gizisnap.admin_email');
        if ($adminEmail && strcasecmp($adminEmail, (string) $googleUser->getEmail()) === 0) {
            $user->role = Role::Admin;
        }

        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $user->isOnboarded()
            ? redirect()->intended(route('dashboard'))
            : redirect()->route('onboarding.show');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Anda sudah keluar.');
    }
}
