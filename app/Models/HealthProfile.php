<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use Database\Factories\HealthProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'gender', 'birth_date', 'height_cm', 'weight_kg', 'activity_level', 'goal', 'allergies', 'dietary_preference'])]
class HealthProfile extends Model
{
    /** @use HasFactory<HealthProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'activity_level' => ActivityLevel::class,
            'goal' => Goal::class,
            'birth_date' => 'date',
            'height_cm' => 'float',
            'weight_kg' => 'float',
            'allergies' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function age(): int
    {
        return (int) $this->birth_date->diffInYears(now());
    }

    public function isMinor(): bool
    {
        return $this->age() < (int) config('gizisnap.targets.adult_age');
    }
}
