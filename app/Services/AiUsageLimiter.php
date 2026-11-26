<?php

namespace App\Services;

use App\Exceptions\AiFailure;
use App\Models\AiUsageLog;
use App\Models\User;
use Laravel\Ai\Responses\Data\Usage;

/**
 * Batas pemakaian AI per pengguna per hari. Yang dihitung hanya
 * panggilan yang berhasil, jadi kegagalan tidak mengurangi kuota.
 */
class AiUsageLimiter
{
    public function limit(string $feature): int
    {
        return match ($feature) {
            'scan' => (int) config('gizisnap.ai.daily_scan_limit'),
            'chat' => (int) config('gizisnap.ai.daily_chat_limit'),
            default => PHP_INT_MAX,
        };
    }

    public function usedToday(User $user, string $feature): int
    {
        return AiUsageLog::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature)
            ->where('status', 'success')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function remaining(User $user, string $feature): int
    {
        return max(0, $this->limit($feature) - $this->usedToday($user, $feature));
    }

    public function ensureAvailable(User $user, string $feature): void
    {
        if ($this->remaining($user, $feature) <= 0) {
            throw AiFailure::quotaExceeded($feature, $this->limit($feature));
        }
    }

    public function record(User $user, string $feature, string $model, ?Usage $usage, int $durationMs, string $status, ?string $errorCode = null): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => $feature,
            'model' => mb_substr($model, 0, 60),
            'input_tokens' => $usage?->inputTokens ?? 0,
            'output_tokens' => $usage?->outputTokens ?? 0,
            'duration_ms' => $durationMs,
            'status' => $status,
            'error_code' => $errorCode,
            'created_at' => now(),
        ]);
    }
}
