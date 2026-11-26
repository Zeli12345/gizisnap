<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meal_type' => MealType::Lunch,
            'eaten_at' => now()->setTime(12, 0),
            'title' => 'Nasi ayam',
            'source' => 'manual',
            'total_calories_kcal' => 500,
            'total_protein_g' => 25,
            'total_carbs_g' => 60,
            'total_fat_g' => 15,
        ];
    }
}
