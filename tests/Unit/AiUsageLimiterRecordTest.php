<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AiUsageLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\Data\Usage;
use Tests\TestCase;

class AiUsageLimiterRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemakaian_sukses_dicatat_dengan_token_dan_durasi(): void
    {
        $user = User::factory()->create();

        app(AiUsageLimiter::class)->record(
            $user,
            'scan',
            str_repeat('m', 80),
            new Usage(120, 30),
            987,
            'success',
        );

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'scan',
            'model' => str_repeat('m', 60),
            'input_tokens' => 120,
            'output_tokens' => 30,
            'duration_ms' => 987,
            'status' => 'success',
            'error_code' => null,
        ]);
    }

    public function test_kegagalan_dicatat_tanpa_token(): void
    {
        $user = User::factory()->create();

        app(AiUsageLimiter::class)->record(
            $user,
            'chat',
            'fake',
            null,
            12,
            'error',
            'connection',
        );

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'chat',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'duration_ms' => 12,
            'status' => 'error',
            'error_code' => 'connection',
        ]);
    }
}
