<?php

namespace App\Services;

use App\Ai\Agents\NutritionCoach;
use App\Ai\FakeResponses;
use App\Exceptions\AiFailure;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly AiUsageLimiter $limiter,
        private readonly DailySummaryService $summary,
    ) {}

    public function startConversation(User $user, string $firstMessage): ChatConversation
    {
        return $user->chatConversations()->create([
            'title' => Str::limit(Str::squish($firstMessage), 60, '…'),
            'last_message_at' => now(),
        ]);
    }

    /**
     * Simpan pesan pengguna, minta jawaban AI, simpan jawabannya.
     * Kalau AI gagal, pesan pengguna tetap tersimpan dan AiFailure dilempar.
     *
     * @return array{user: ChatMessage, assistant: ChatMessage}
     */
    public function send(ChatConversation $conversation, string $text): array
    {
        $user = $conversation->user;
        $this->limiter->ensureAvailable($user, 'chat');

        $userMessage = $conversation->messages()->create(['role' => 'user', 'content' => trim($text)]);
        $conversation->update(['last_message_at' => now()]);

        $assistant = $this->reply($conversation, $userMessage);

        return ['user' => $userMessage, 'assistant' => $assistant];
    }

    /**
     * Minta jawaban untuk pesan pengguna yang sudah tersimpan (dipakai juga untuk "Kirim ulang").
     */
    public function reply(ChatConversation $conversation, ChatMessage $userMessage): ChatMessage
    {
        $user = $conversation->user;
        $this->limiter->ensureAvailable($user, 'chat');

        $fake = (bool) config('gizisnap.ai.fake');
        $model = $fake ? 'fake' : (string) config('gizisnap.ai.chat_model');
        $started = microtime(true);
        $usage = null;

        try {
            if ($fake) {
                $remaining = $this->summary->forDate($user, now())['remaining_kcal'];
                $text = FakeResponses::chat($userMessage->content, $remaining);
            } else {
                $agent = new NutritionCoach(
                    userContext: $this->summary->contextForChat($user),
                    conversation: $conversation,
                    excludeMessageId: $userMessage->id,
                );

                $response = retry(2, fn () => $agent->prompt(
                    $userMessage->content,
                    provider: config('gizisnap.ai.provider'),
                    model: $model,
                    timeout: (int) config('gizisnap.ai.timeout'),
                ), 500, fn (Throwable $e) => $e instanceof ProviderConnectionException);

                $usage = $response->usage;
                $text = trim($response->text);
            }

            if ($text === '') {
                throw new AiFailure('Jawaban kosong dari AI. Coba kirim ulang.', 'empty');
            }
        } catch (Throwable $e) {
            $failure = $e instanceof AiFailure ? $e : AiFailure::fromThrowable($e);
            Log::warning('Chat gagal', ['conversation_id' => $conversation->id, 'error' => $e->getMessage()]);
            $this->limiter->record($user, 'chat', $model, $usage, $this->elapsed($started), 'error', $failure->reason);

            throw $failure;
        }

        $this->limiter->record($user, 'chat', $model, $usage, $this->elapsed($started), 'success');

        $assistant = $conversation->messages()->create(['role' => 'assistant', 'content' => $text]);
        $conversation->update(['last_message_at' => now()]);

        return $assistant;
    }

    /**
     * Pesan pengguna terakhir yang belum dijawab, kalau ada.
     */
    public function pendingUserMessage(ChatConversation $conversation): ?ChatMessage
    {
        $last = $conversation->messages()->latest('id')->first();

        return $last && $last->role === 'user' ? $last : null;
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
