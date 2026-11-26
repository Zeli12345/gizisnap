<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Food;
use App\Models\Meal;
use App\Models\Scan;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(6)->startOfDay();

        $daily = AiUsageLog::where('created_at', '>=', $since)
            ->get(['feature', 'status', 'created_at'])
            ->groupBy(fn ($log) => $log->created_at->toDateString());

        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i));

        $recentErrors = AiUsageLog::with('user:id,name')
            ->where('status', 'error')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'active_week' => User::where('last_login_at', '>=', $since)->count(),
                'meals_week' => Meal::where('created_at', '>=', $since)->count(),
                'scans_week' => Scan::where('created_at', '>=', $since)->count(),
                'ai_errors_week' => AiUsageLog::where('created_at', '>=', $since)->where('status', 'error')->count(),
                'foods' => Food::count(),
            ],
            'recentErrors' => $recentErrors,
            'aiUnavailable' => $recentErrors->first()?->error_code === 'unavailable',
            'chart' => [
                'labels' => $days->map(fn ($d) => $d->translatedFormat('D j')),
                'scan' => $days->map(fn ($d) => $daily->get($d->toDateString(), collect())->where('feature', 'scan')->count()),
                'chat' => $days->map(fn ($d) => $daily->get($d->toDateString(), collect())->where('feature', 'chat')->count()),
            ],
        ]);
    }
}
