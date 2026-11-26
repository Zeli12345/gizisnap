<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Models\HealthProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthProfile>
 */
class HealthProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_date' => now()->subYears(fake()->numberBetween(18, 40))->toDateString(),
            'height_cm' => fake()->numberBetween(150, 185),
            'weight_kg' => fake()->numberBetween(45, 85),
            'activity_level' => ActivityLevel::Moderate,
            'goal' => Goal::Maintain,
        ];
    }
}
