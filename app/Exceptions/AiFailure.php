<?php

namespace App\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\InvalidApiKeyException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;

/**
 * Kegagalan AI yang pesannya aman ditampilkan ke pengguna.
 */
class AiFailure extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason = 'error',
    ) {
        parent::__construct($message);
    }

    public static function quotaExceeded(string $feature, int $limit): self
    {
        $what = match ($feature) {
            'scan' => 'scan',
            'report' => 'laporan',
            'insight' => 'laporan',
            default => 'pesan chat',
        };

        return new self("Kuota {$what} hari ini habis ({$limit} dari {$limit}). Coba lagi besok.", 'quota');
    }

    public static function fromThrowable(\Throwable $e): self
    {
        return match (true) {
            $e instanceof RateLimitedException,
            $e instanceof ProviderOverloadedException => new self('Layanan AI sedang ramai. Coba lagi sebentar lagi.', 'rate_limited'),
            $e instanceof InsufficientCreditsException,
            $e instanceof InvalidApiKeyException => new self('Layanan AI sedang tidak tersedia. Beri tahu admin.', 'unavailable'),
            $e instanceof ProviderConnectionException,
            $e instanceof ConnectionException => new self('Tidak bisa terhubung ke layanan AI. Periksa internet lalu coba lagi.', 'connection'),
            default => new self('Analisis gagal. Coba lagi.', 'error'),
        };
    }
}
