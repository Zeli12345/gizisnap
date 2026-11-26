<?php

namespace App\Services;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Models\HealthProfile;

/**
 * Semua rumus gizi ada di sini agar bisa dites tanpa browser.
 * Energi istirahat memakai rumus Mifflin-St Jeor.
 */
class NutritionCalculator
{
    public function restingEnergy(Gender $gender, int $age, float $heightCm, float $weightKg): float
    {
        $base = 10 * $weightKg + 6.25 * $heightCm - 5 * $age;

        return $gender === Gender::Male ? $base + 5 : $base - 161;
    }

    /**
     * @return array{calories_kcal:int, protein_g:float, carbs_g:float, fat_g:float, water_ml:int, resting_kcal:int, tdee_kcal:int}
     */
    public function targets(Gender $gender, int $age, float $heightCm, float $weightKg, ActivityLevel $activity, Goal $goal): array
    {
        $resting = $this->restingEnergy($gender, $age, $heightCm, $weightKg);
        $tdee = $resting * $activity->factor();

        // Remaja tidak diberi pengurangan kalori.
        $isMinor = $age < (int) config('gizisnap.targets.adult_age');
        $adjustment = ($isMinor && $goal === Goal::Lose) ? 0.0 : $goal->adjustment();

        $calories = max($resting, $tdee * (1 + $adjustment));
        $calories = (int) round($calories);

        $split = config('gizisnap.targets.macro_split');

        return [
            'calories_kcal' => $calories,
            'protein_g' => round($calories * $split['protein'] / 4, 1),
            'fat_g' => round($calories * $split['fat'] / 9, 1),
            'carbs_g' => round($calories * $split['carbs'] / 4, 1),
            'water_ml' => (int) config('gizisnap.targets.water_ml'),
            'resting_kcal' => (int) round($resting),
            'tdee_kcal' => (int) round($tdee),
        ];
    }

    public function targetsForProfile(HealthProfile $profile): array
    {
        return $this->targets(
            $profile->gender,
            $profile->age(),
            $profile->height_cm,
            $profile->weight_kg,
            $profile->activity_level,
            $profile->goal,
        );
    }

    public function bmi(float $weightKg, float $heightCm): float
    {
        $meters = $heightCm / 100;

        return round($weightKg / ($meters * $meters), 1);
    }

    /**
     * Kategori IMT dewasa menurut Kemenkes RI. Untuk usia di bawah 18 tahun
     * tidak diberi label karena perlu grafik pertumbuhan sesuai umur.
     */
    public function bmiCategory(float $bmi, int $age): ?string
    {
        if ($age < (int) config('gizisnap.targets.adult_age')) {
            return null;
        }

        return match (true) {
            $bmi < 18.5 => 'Kurus',
            $bmi <= 25.0 => 'Normal',
            $bmi <= 27.0 => 'Berat badan lebih',
            default => 'Obesitas',
        };
    }

    /**
     * Cek kewajaran: kalori dibandingkan 4P + 4K + 9L.
     */
    public function caloriesLookConsistent(float $calories, float $protein, float $carbs, float $fat): bool
    {
        $expected = 4 * $protein + 4 * $carbs + 9 * $fat;

        if ($expected < 20 && $calories < 20) {
            return true;
        }

        $reference = max($expected, 1);

        return abs($calories - $expected) / $reference <= 0.30;
    }
}
