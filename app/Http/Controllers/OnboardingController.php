<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Http\Requests\OnboardingRequest;
use App\Services\NutritionCalculator;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function show()
    {
        return view('onboarding.show', [
            'genders' => Gender::cases(),
            'activityLevels' => ActivityLevel::cases(),
            'goals' => Goal::cases(),
        ]);
    }

    public function store(OnboardingRequest $request, NutritionCalculator $calculator)
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user, $calculator) {
            $profile = $user->healthProfile()->create([
                ...$request->safe()->except('allergies'),
                'allergies' => $request->allergyList(),
            ]);

            $targets = $calculator->targetsForProfile($profile);

            $user->dailyTargets()->create([
                'calories_kcal' => $targets['calories_kcal'],
                'protein_g' => $targets['protein_g'],
                'carbs_g' => $targets['carbs_g'],
                'fat_g' => $targets['fat_g'],
                'water_ml' => $targets['water_ml'],
                'source' => 'auto',
                'effective_from' => today(),
            ]);

            $user->weightLogs()->create([
                'weight_kg' => $profile->weight_kg,
                'logged_on' => today(),
            ]);

            $user->update(['onboarded_at' => now()]);
        });

        return redirect()->route('dashboard', ['welcome' => 1]);
    }
}
