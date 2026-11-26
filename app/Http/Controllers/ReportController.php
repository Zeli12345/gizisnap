<?php

namespace App\Http\Controllers;

use App\Services\DailySummaryService;
use App\Services\NutritionCalculator;
use App\Services\PlateBalance;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(
        Request $request,
        DailySummaryService $summary,
        PlateBalance $plate,
        NutritionCalculator $calculator,
    ) {
        $period = in_array((int) $request->query('period'), [7, 30], true) ? (int) $request->query('period') : 7;
        $user = $request->user();
        $series = $summary->series($user, $period);
        $profile = $user->healthProfile;

        $topFoods = $user->meals()
            ->where('eaten_at', '>=', now()->subDays($period - 1)->startOfDay())
            ->join('meal_items', 'meal_items.meal_id', '=', 'meals.id')
            ->selectRaw('meal_items.name, COUNT(*) as times, SUM(meal_items.calories_kcal) as calories')
            ->groupBy('meal_items.name')
            ->orderByDesc('times')
            ->limit(5)
            ->get();

        // Berat badan ditampilkan di sini juga supaya tidak jadi halaman terpisah.
        $weightLogs = $user->weightLogs()->orderByDesc('logged_on')->take(60)->get();
        $latestWeight = $weightLogs->first();
        $firstWeight = $weightLogs->last();
        $bmi = $latestWeight ? $calculator->bmi($latestWeight->weight_kg, $profile->height_cm) : null;
        $weightChartLogs = $weightLogs->take(30)->reverse()->values();

        return view('reports.index', [
            'period' => $period,
            'series' => $series,
            'stats' => $summary->periodStats($series),
            'balance' => $plate->balanceOverPeriod($user, $series),
            'target' => $user->targetFor(now()),
            'topFoods' => $topFoods,
            'weightLogs' => $weightLogs,
            'latestWeight' => $latestWeight,
            'firstWeight' => $firstWeight,
            'weightChange' => $latestWeight && $firstWeight && $latestWeight->id !== $firstWeight->id
                ? round($latestWeight->weight_kg - $firstWeight->weight_kg, 1)
                : null,
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? $calculator->bmiCategory($bmi, $profile->age()) : null,
            'weightChart' => [
                'labels' => $weightChartLogs->map(fn ($l) => $l->logged_on->translatedFormat('j M')),
                'values' => $weightChartLogs->pluck('weight_kg'),
            ],
            'chart' => [
                'labels' => $series->map(fn ($d) => $d['date']->translatedFormat($period === 7 ? 'D' : 'j M'))->values(),
                'calories' => $series->pluck('calories_kcal')->values(),
                'targets' => $series->pluck('target_kcal')->values(),
                'protein' => $series->pluck('protein_g')->values(),
                'carbs' => $series->pluck('carbs_g')->values(),
                'fat' => $series->pluck('fat_g')->values(),
            ],
        ]);
    }
}
