<?php

namespace App\Ai\Concerns;

use Laravel\Ai\Enums\Lab;

/**
 * Mengirim pilihan khusus penyedia (reasoning_effort) hanya ke penyedia
 * yang sedang dipakai GiziSnap.
 *
 * CommandCode memakai reasoning_effort: off|low|medium|high|xhigh|max.
 * Penyedia lain (misalnya OpenAI) menolak kunci ini untuk model non-reasoning,
 * jadi kunci hanya dikirim kalau nama penyedia cocok dengan AI_PROVIDER.
 */
trait HasReasoningEffort
{
    public function providerOptions(Lab|string $provider): array
    {
        $name = $provider instanceof Lab ? $provider->value : $provider;

        if ($name !== (string) config('gizisnap.ai.provider')) {
            return [];
        }

        $effort = config('gizisnap.ai.reasoning_effort');

        return filled($effort) ? ['reasoning_effort' => (string) $effort] : [];
    }
}
