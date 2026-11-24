<?php

namespace App\Models;

use App\Enums\MealType;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'scan_id', 'meal_type', 'eaten_at', 'title', 'source', 'notes',
    'total_calories_kcal', 'total_protein_g', 'total_carbs_g', 'total_fat_g', 'total_fiber_g', 'total_sugar_g', 'total_sodium_mg'])]
class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use HasFactory, SoftDeletes;

    public const NUTRIENTS = ['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g', 'sodium_mg'];

    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'eaten_at' => 'datetime',
            'total_calories_kcal' => 'float',
            'total_protein_g' => 'float',
            'total_carbs_g' => 'float',
            'total_fat_g' => 'float',
            'total_fiber_g' => 'float',
            'total_sugar_g' => 'float',
            'total_sodium_mg' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealItem::class);
    }

    /**
     * Hitung ulang kolom total_* dari item. Server selalu yang menjumlah.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        foreach (self::NUTRIENTS as $nutrient) {
            $this->{'total_'.$nutrient} = round($items->sum($nutrient), 1);
        }

        $this->save();
    }
}
