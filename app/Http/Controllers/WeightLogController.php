<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeightLogRequest;
use App\Models\WeightLog;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;

class WeightLogController extends Controller
{
    public function index(Request $request, NutritionCalculator $calculator)
    {
        $user = $request->user();
        $profile = $user->healthProfile;
        $logs = $user->weightLogs()->orderByDesc('logged_on')->take(60)->get();
        $latest = $logs->first();
        $bmi = $latest ? $calculator->bmi($latest->weight_kg, $profile->height_cm) : null;
        $chartLogs = $logs->take(30)->reverse()->values();

        return view('weight.index', [
            'logs' => $logs,
            'latest' => $latest,
            'first' => $logs->last(),
            'profile' => $profile,
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? $calculator->bmiCategory($bmi, $profile->age()) : null,
            'chart' => [
                'labels' => $chartLogs->map(fn ($l) => $l->logged_on->translatedFormat('j M')),
                'values' => $chartLogs->pluck('weight_kg'),
            ],
        ]);
    }

    public function store(WeightLogRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $user->weightLogs()->updateOrCreate(
            ['logged_on' => $data['logged_on']],
            ['weight_kg' => $data['weight_kg'], 'note' => $data['note'] ?? null],
        );

        // Berat di profil mengikuti catatan terbaru.
        $latest = $user->weightLogs()->orderByDesc('logged_on')->first();
        $user->healthProfile->update(['weight_kg' => $latest->weight_kg]);

        return back()->with('success', 'Berat badan tercatat.');
    }

    public function destroy(Request $request, WeightLog $weightLog)
    {
        abort_unless($weightLog->user_id === $request->user()->id, 403);

        if ($request->user()->weightLogs()->count() <= 1) {
            return back()->with('error', 'Catatan berat terakhir tidak bisa dihapus.');
        }

        $weightLog->delete();

        return back()->with('success', 'Catatan berat dihapus.');
    }
}
