<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'role', 'onboarded_at', 'last_login_at'])]
#[Hidden(['password', 'google_id', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'password' => 'hashed',
            'onboarded_at' => 'datetime',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function healthProfile(): HasOne
    {
        return $this->hasOne(HealthProfile::class);
    }

    public function dailyTargets(): HasMany
    {
        return $this->hasMany(DailyTarget::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function weightLogs(): HasMany
    {
        return $this->hasMany(WeightLog::class);
    }

    public function waterLogs(): HasMany
    {
        return $this->hasMany(WaterLog::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(UserVisit::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    /**
     * Target yang berlaku pada tanggal tertentu: baris dengan
     * effective_from terbaru yang tidak melewati tanggal itu.
     */
    public function targetFor(CarbonInterface $date): ?DailyTarget
    {
        return $this->dailyTargets()
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->orderByDesc('effective_from')
            ->first()
            ?? $this->dailyTargets()->orderBy('effective_from')->first();
    }

    public function firstName(): string
    {
        return str($this->name)->before(' ')->toString();
    }

    public function initials(): string
    {
        return str($this->name)->explode(' ')->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
