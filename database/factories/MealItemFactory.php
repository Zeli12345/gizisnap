<?php

namespace Database\Factories;

use App\Models\Meal;
use App\Models\MealItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealItem>
 */
class MealItemFactory extends Factory
{
    protected $model = MealItem::class;

    public function definition(): array
    {
        return [
            'meal_id' => Meal::factory(),
            'food_id' => null,
            'name' => 'Nasi putih',
            'portion_desc' => '1 porsi',
            'weight_g' => 200,
            'calories_kcal' => 360,
            'protein_g' => 6.8,
            'carbs_g' => 79.2,
            'fat_g' => 0.6,
            'fiber_g' => 0.8,
            'sugar_g' => 0.2,
            'sodium_mg' => 2,
            'is_edited' => false,
        ];
    }
}
