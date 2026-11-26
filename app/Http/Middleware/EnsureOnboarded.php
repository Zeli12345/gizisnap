<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang belum mengisi profil kesehatan dialihkan ke onboarding.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isOnboarded()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Lengkapi profil terlebih dahulu.'], 409)
                : redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
