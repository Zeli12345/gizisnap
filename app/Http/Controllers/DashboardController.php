<?php

namespace App\Http\Controllers;

use App\Models\UserVisit;
use App\Services\DailySummaryService;
use App\Services\MealTimeline;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DailySummaryService $summary,
        MealTimeline $timeline,
    ) {
        $date = $this->resolveDate($request->query('date'));
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Halaman utama pengguna: satu kunjungan per hari, bukan per muat halaman.
        UserVisit::recordFor($user);

        return view('dashboard.index', [
            'summary' => $summary->forDate($user, $date),
            'date' => $date,
            'isToday' => $date->isToday(),
            'streak' => $summary->streak($user),
            'timeline' => $timeline->forDate($user, $date),
            'welcome' => $request->boolean('welcome'),
        ]);
    }

    private function resolveDate(?string $value): CarbonImmutable
    {
        try {
            $date = $value ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay() : CarbonImmutable::today();
        } catch (\Throwable) {
            $date = CarbonImmutable::today();
        }

        return $date->isFuture() ? CarbonImmutable::today() : $date;
    }
}
