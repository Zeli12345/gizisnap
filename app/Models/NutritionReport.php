<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'period_days', 'period_start', 'period_end', 'fingerprint',
    'status', 'content', 'model', 'error_message', 'generated_at',
])]
class NutritionReport extends Model
{
    protected function casts(): array
    {
        return [
            'period_days' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'content' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && ! empty($this->content);
    }
}
