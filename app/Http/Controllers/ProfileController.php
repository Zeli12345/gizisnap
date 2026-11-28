<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\TargetsRequest;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request, NutritionCalculator $calculator)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('profile.admin', ['user' => $user]);
        }

        $profile = $user->healthProfile;
        $bmi = $calculator->bmi($profile->weight_kg, $profile->height_cm);

        return view('profile.show', [
            'user' => $user,
            'profile' => $profile,
            'target' => $user->targetFor(now()),
            'suggested' => $calculator->targetsForProfile($profile),
            'bmi' => $bmi,
            'bmiCategory' => $calculator->bmiCategory($bmi, $profile->age()),
            'genders' => Gender::cases(),
            'activityLevels' => ActivityLevel::cases(),
            'goals' => Goal::cases(),
        ]);
    }

    /**
     * Ubah data diri. Kalau target masih otomatis, target ikut dihitung ulang.
     */
    public function update(ProfileRequest $request, NutritionCalculator $calculator)
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user, $calculator) {
            $user->update(['name' => $request->validated('name')]);

            $profile = $user->healthProfile;
            $weightChanged = (float) $profile->weight_kg !== (float) $request->validated('weight_kg');

            $profile->update([
                ...$request->safe()->except(['name', 'allergies']),
                'allergies' => $request->allergyList(),
            ]);

            if ($weightChanged) {
                $user->weightLogs()->updateOrCreate(['logged_on' => today()->toDateString()], ['weight_kg' => $profile->weight_kg]);
            }

            if ($user->targetFor(now())?->source !== 'manual') {
                $this->saveTargets($user, $calculator->targetsForProfile($profile->refresh()), 'auto');
            }
        });

        return back()->with('success', 'Profil tersimpan.');
    }

    public function updateTargets(TargetsRequest $request)
    {
        $this->saveTargets($request->user(), $request->validated(), 'manual');

        return back()->with('success', 'Target harian diubah. Target baru berlaku mulai hari ini.');
    }

    public function resetTargets(Request $request, NutritionCalculator $calculator)
    {
        $user = $request->user();
        $this->saveTargets($user, $calculator->targetsForProfile($user->healthProfile), 'auto');

        return back()->with('success', 'Target kembali dihitung otomatis dari profil.');
    }

    /**
     * Hapus akun beserta semua data dan foto.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'in:HAPUS'],
        ], [
            'confirmation.in' => 'Ketik HAPUS dengan huruf besar untuk mengonfirmasi.',
            'confirmation.required' => 'Ketik HAPUS untuk mengonfirmasi.',
        ]);

        $user = $request->user();

        Storage::disk(config('gizisnap.scan.disk'))->deleteDirectory(config('gizisnap.scan.directory').'/'.$user->id);

        Auth::logout();
        DB::transaction(function () use ($user) {
            $user->meals()->withTrashed()->forceDelete();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Akun dan semua data Anda sudah dihapus.');
    }

    private function saveTargets($user, array $values, string $source): void
    {
        $user->dailyTargets()->updateOrCreate(
            ['effective_from' => today()->toDateString()],
            [
                'calories_kcal' => (int) $values['calories_kcal'],
                'protein_g' => $values['protein_g'],
                'carbs_g' => $values['carbs_g'],
                'fat_g' => $values['fat_g'],
                'water_ml' => (int) ($values['water_ml'] ?? config('gizisnap.targets.water_ml')),
                'source' => $source,
            ],
        );
    }
}
