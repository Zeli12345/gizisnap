<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    protected $model = Meal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scan_id' => null,
            'meal_type' => MealType::Lunch,
            'eaten_at' => now(),
            'title' => 'Nasi putih dan ayam',
            'source' => 'manual',
            'notes' => null,
            'total_calories_kcal' => 0,
            'total_protein_g' => 0,
            'total_carbs_g' => 0,
            'total_fat_g' => 0,
            'total_fiber_g' => 0,
            'total_sugar_g' => 0,
            'total_sodium_mg' => 0,
        ];
    }

    public function breakfast(): static
    {
        return $this->state(fn () => ['meal_type' => MealType::Breakfast]);
    }

    public function dinner(): static
    {
        return $this->state(fn () => ['meal_type' => MealType::Dinner]);
    }

    public function snack(): static
    {
        return $this->state(fn () => ['meal_type' => MealType::Snack]);
    }
}
