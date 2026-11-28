<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Models\HealthProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthProfile>
 */
class HealthProfileFactory extends Factory
{
    protected $model = HealthProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => Gender::Male,
            'birth_date' => now()->subYears(20)->toDateString(),
            'height_cm' => 170,
            'weight_kg' => 65,
            'activity_level' => ActivityLevel::Moderate,
            'goal' => Goal::Maintain,
            'allergies' => null,
            'dietary_preference' => null,
            'health_conditions' => null,
        ];
    }

    public function female(): static
    {
        return $this->state(fn () => ['gender' => Gender::Female]);
    }

    public function minor(): static
    {
        return $this->state(fn () => ['birth_date' => now()->subYears(15)->toDateString()]);
    }

    public function losingWeight(): static
    {
        return $this->state(fn () => ['goal' => Goal::Lose]);
    }
}
