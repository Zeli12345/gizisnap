<?php

namespace Tests\Unit;

use App\Exceptions\AiFailure;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Tests\TestCase;

class AiFailureTest extends TestCase
{
    public function test_pesan_kuota_per_fitur(): void
    {
        $scan = AiFailure::quotaExceeded('scan', 10);
        $this->assertSame('Kuota scan hari ini habis (10 dari 10). Coba lagi besok.', $scan->getMessage());
        $this->assertSame('quota', $scan->reason);

        $this->assertStringContainsString('Kuota laporan', AiFailure::quotaExceeded('report', 3)->getMessage());
        $this->assertStringContainsString('Kuota laporan', AiFailure::quotaExceeded('insight', 3)->getMessage());
        $this->assertStringContainsString('Kuota pesan chat', AiFailure::quotaExceeded('chat', 30)->getMessage());
    }

    public function test_jenis_error_diterjemahkan_ke_pesan_yang_aman(): void
    {
        $rateLimited = AiFailure::fromThrowable(RateLimitedException::forProvider('openai'));
        $this->assertSame('rate_limited', $rateLimited->reason);
        $this->assertStringContainsString('sedang ramai', $rateLimited->getMessage());

        $overloaded = AiFailure::fromThrowable(ProviderOverloadedException::forProvider('openai'));
        $this->assertSame('rate_limited', $overloaded->reason);

        $credits = AiFailure::fromThrowable(InsufficientCreditsException::forProvider('openai'));
        $this->assertSame('unavailable', $credits->reason);

        $connection = AiFailure::fromThrowable(ProviderConnectionException::forProvider('openai'));
        $this->assertSame('connection', $connection->reason);

        $httpConnection = AiFailure::fromThrowable(new ConnectionException('gagal'));
        $this->assertSame('connection', $httpConnection->reason);

        $unknown = AiFailure::fromThrowable(new RuntimeException('rahasia internal'));
        $this->assertSame('error', $unknown->reason);
        $this->assertSame('Analisis gagal. Coba lagi.', $unknown->getMessage());
    }
}
