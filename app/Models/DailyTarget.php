<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'water_ml', 'source', 'effective_from'])]
class DailyTarget extends Model
{
    protected function casts(): array
    {
        return [
            'calories_kcal' => 'integer',
            'protein_g' => 'float',
            'carbs_g' => 'float',
            'fat_g' => 'float',
            'water_ml' => 'integer',
            'effective_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Simpan sebagai Y-m-d agar pencarian per tanggal sama di MySQL dan SQLite.
     */
    protected function effectiveFrom(): Attribute
    {
        return Attribute::set(fn ($value) => $value ? Carbon::parse($value)->toDateString() : null);
    }
}
