<?php

namespace Tests\Unit;

use App\Exceptions\AiFailure;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AiUsageLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLimiterTest extends TestCase
{
    use RefreshDatabase;

    private function log(User $user, string $feature, string $status = 'success', ?string $createdAt = null): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => $feature,
            'model' => 'fake',
            'input_tokens' => 10,
            'output_tokens' => 20,
            'duration_ms' => 100,
            'status' => $status,
            'error_code' => $status === 'error' ? 'error' : null,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    public function test_batas_harian_per_fitur(): void
    {
        $limiter = new AiUsageLimiter;

        config([
            'gizisnap.ai.daily_scan_limit' => 2,
            'gizisnap.ai.daily_chat_limit' => 3,
            'gizisnap.ai.daily_report_limit' => 1,
        ]);

        $this->assertSame(2, $limiter->limit('scan'));
        $this->assertSame(3, $limiter->limit('chat'));
        $this->assertSame(1, $limiter->limit('report'));
        $this->assertSame(1, $limiter->limit('insight'));
        $this->assertSame(PHP_INT_MAX, $limiter->limit('lainnya'));
    }

    public function test_hanya_panggilan_sukses_hari_ini_yang_memakan_kuota(): void
    {
        $user = User::factory()->create();
        $limiter = new AiUsageLimiter;

        $this->log($user, 'scan');
        $this->log($user, 'scan', 'error');
        $this->log($user, 'scan', 'success', now()->subDay()->toDateTimeString());
        $this->log($user, 'chat');

        $this->assertSame(1, $limiter->usedToday($user, 'scan'));
        $this->assertSame(29, $limiter->remaining($user, 'chat'));
    }

    public function test_kuota_habis_melempar_ai_failure(): void
    {
        $user = User::factory()->create();
        config(['gizisnap.ai.daily_scan_limit' => 2]);

        $this->log($user, 'scan');
        $this->log($user, 'scan');

        $limiter = new AiUsageLimiter;

        $this->assertSame(0, $limiter->remaining($user, 'scan'));

        $this->expectException(AiFailure::class);
        $this->expectExceptionMessage('Kuota scan hari ini habis (2 dari 2). Coba lagi besok.');

        $limiter->ensureAvailable($user, 'scan');
    }
}
