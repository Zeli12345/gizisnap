<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'weight_kg', 'logged_on', 'note'])]
class WeightLog extends Model
{
    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'logged_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Simpan sebagai Y-m-d agar pencarian per tanggal sama di MySQL dan SQLite.
     */
    protected function loggedOn(): Attribute
    {
        return Attribute::set(fn ($value) => $value ? Carbon::parse($value)->toDateString() : null);
    }
}
