<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman onboarding hanya untuk yang belum mengisi profil.
 */
class EnsureNotOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isOnboarded()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
