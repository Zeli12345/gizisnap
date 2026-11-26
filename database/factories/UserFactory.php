<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\HealthProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'google_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'role' => Role::User,
            'onboarded_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    /**
     * Pengguna yang sudah mengisi profil dan punya target harian.
     */
    public function onboarded(array $profile = []): static
    {
        return $this->state(fn () => ['onboarded_at' => now()])
            ->afterCreating(function (User $user) use ($profile) {
                HealthProfile::factory()->for($user)->create($profile);
                $user->dailyTargets()->create([
                    'calories_kcal' => 2000,
                    'protein_g' => 100,
                    'carbs_g' => 275,
                    'fat_g' => 55.6,
                    'water_ml' => 2000,
                    'source' => 'auto',
                    'effective_from' => today()->subDays(30),
                ]);
            });
    }
}
