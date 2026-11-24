<?php

namespace App\Models;

use App\Enums\ScanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'image_path', 'status', 'dish_name', 'confidence', 'ai_result', 'error_message'])]
class Scan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ScanStatus::class,
            'confidence' => 'float',
            'ai_result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meal(): HasOne
    {
        return $this->hasOne(Meal::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return $this->ai_result['items'] ?? [];
    }

    public function totals(): array
    {
        $sum = fn (string $key) => round(collect($this->items())->sum($key), 1);

        return [
            'calories_kcal' => $sum('calories_kcal'),
            'protein_g' => $sum('protein_g'),
            'carbs_g' => $sum('carbs_g'),
            'fat_g' => $sum('fat_g'),
        ];
    }

    public function isLowConfidence(): bool
    {
        return $this->confidence !== null && $this->confidence < (float) config('gizisnap.ai.low_confidence');
    }
}
