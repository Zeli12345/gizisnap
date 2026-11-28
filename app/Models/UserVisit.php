<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'visited_on'])]
class UserVisit extends Model
{
    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat kunjungan harian. Hari yang sama hanya dihitung sekali,
     * jadi Senin + Selasa + Rabu bernilai 3.
     */
    public static function recordFor(User $user, ?CarbonInterface $date = null): void
    {
        static::query()->insertOrIgnore([
            'user_id' => $user->id,
            'visited_on' => ($date ?? today())->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
