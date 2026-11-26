<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Membuat dan mengubah catatan makan beserta itemnya,
 * lalu menghitung ulang total di server.
 */
class MealService
{
    /**
     * @param  array<string, mixed>  $attributes  meal_type, eaten_at, title, notes, source, scan_id
     * @param  list<array<string, mixed>>  $items
     */
    public function create(User $user, array $attributes, array $items): Meal
    {
        return DB::transaction(function () use ($user, $attributes, $items) {
            $meal = $user->meals()->create($attributes);
            $this->replaceItems($meal, $items);

            return $meal->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $items
     */
    public function update(Meal $meal, array $attributes, array $items): Meal
    {
        return DB::transaction(function () use ($meal, $attributes, $items) {
            $meal->update($attributes);
            $this->replaceItems($meal, $items);

            return $meal->refresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function replaceItems(Meal $meal, array $items): void
    {
        $meal->items()->delete();

        foreach ($items as $item) {
            $meal->items()->create([
                'food_id' => $item['food_id'] ?? null,
                'name' => trim($item['name']),
                'portion_desc' => trim($item['portion_desc'] ?? '1 porsi') ?: '1 porsi',
                'weight_g' => $this->number($item['weight_g'] ?? null, nullable: true),
                'calories_kcal' => $this->number($item['calories_kcal'] ?? 0),
                'protein_g' => $this->number($item['protein_g'] ?? 0),
                'carbs_g' => $this->number($item['carbs_g'] ?? 0),
                'fat_g' => $this->number($item['fat_g'] ?? 0),
                'fiber_g' => $this->number($item['fiber_g'] ?? 0),
                'sugar_g' => $this->number($item['sugar_g'] ?? 0),
                'sodium_mg' => $this->number($item['sodium_mg'] ?? 0),
                'is_edited' => (bool) ($item['is_edited'] ?? false),
            ]);
        }

        $meal->recalculateTotals();
    }

    private function number(mixed $value, bool $nullable = false): ?float
    {
        if ($value === null || $value === '') {
            return $nullable ? null : 0.0;
        }

        return round(max(0, (float) $value), 1);
    }
}
