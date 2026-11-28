<?php

namespace App\Providers;

use App\Services\DailySummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));
        Date::use(CarbonImmutable::class);

        // Jawaban AI tiruan tidak boleh pernah aktif di produksi, walaupun .env salah isi.
        if ($this->app->environment('production')) {
            config(['gizisnap.ai.fake' => false]);
        }

        // Cincin "hari ini" di menu samping: ada di setiap halaman, jadi dihitung sekali di sini.
        View::composer('components.layouts.app', function ($view) {
            $user = auth()->user();
            $ring = null;

            if ($user && ! $user->isAdmin() && $user->healthProfile) {
                $day = app(DailySummaryService::class)->forDate($user, now());
                $goal = (int) ($day['target']?->calories_kcal ?? 0);
                $eaten = (int) round($day['totals']['calories_kcal']);

                $ring = [
                    'eaten' => $eaten,
                    'goal' => $goal,
                    'pct' => $goal > 0 ? min(100, (int) round($eaten / $goal * 100)) : 0,
                    'over' => $goal > 0 && $eaten > $goal * 1.1,
                    'left' => $goal > 0 ? $goal - $eaten : null,
                ];
            }

            $view->with('dayRing', $ring);
        });

        // Paling banyak N permintaan AI per menit per pengguna.
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute((int) config('gizisnap.ai.per_minute_limit'))
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request) {
                    $message = 'Terlalu banyak permintaan. Tunggu satu menit lalu coba lagi.';

                    return $request->expectsJson()
                        ? response()->json(['message' => $message], 429)
                        : back()->with('error', $message);
                });
        });
    }
}
