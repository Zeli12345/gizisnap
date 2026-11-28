<?php

namespace Tests\Unit;

use App\Exceptions\AiFailure;
use App\Models\AiUsageLog;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chat = app(ChatService::class);
    }

    public function test_judul_percakapan_diringkas_dan_dibatasi(): void
    {
        $user = $this->onboardedUser();

        $conversation = $this->chat->startConversation($user, "  Apa   menu\nsehat untuk sarapan besok pagi ya?  ");

        $this->assertSame('Apa menu sehat untuk sarapan besok pagi ya?', $conversation->title);
        $this->assertNotNull($conversation->last_message_at);

        $long = $this->chat->startConversation($user, str_repeat('kata ', 40));

        $this->assertLessThanOrEqual(61, mb_strlen($long->title));
        $this->assertStringEndsWith('…', $long->title);
    }

    public function test_pesan_pengguna_terakhir_yang_belum_dijawab(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);

        $this->assertNull($this->chat->pendingUserMessage($conversation));

        $conversation->messages()->create(['role' => 'user', 'content' => 'Halo']);
        $this->assertSame('Halo', $this->chat->pendingUserMessage($conversation)->content);

        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Hai']);
        $this->assertNull($this->chat->pendingUserMessage($conversation));
    }

    public function test_kirim_pesan_menyimpan_dua_pesan_dan_log_pemakaian(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);

        $result = $this->chat->send($conversation, '  Apa itu protein?  ');

        $this->assertSame('Apa itu protein?', $result['user']->content);
        $this->assertSame('assistant', $result['assistant']->role);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'chat',
            'model' => 'fake',
            'status' => 'success',
        ]);
    }

    public function test_jawaban_ulang_hanya_menambah_pesan_asisten(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);
        $pending = $conversation->messages()->create(['role' => 'user', 'content' => 'Halo']);

        $assistant = $this->chat->reply($conversation, $pending);

        $this->assertSame('assistant', $assistant->role);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertSame(1, AiUsageLog::where('feature', 'chat')->where('status', 'success')->count());
    }

    public function test_kuota_habis_sebelum_pesan_pengguna_disimpan(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);
        config(['gizisnap.ai.daily_chat_limit' => 0]);

        try {
            $this->chat->send($conversation, 'Halo');
            $this->fail('Kuota seharusnya habis.');
        } catch (AiFailure $e) {
            $this->assertSame('quota', $e->reason);
        }

        $this->assertSame(0, $conversation->messages()->count());
        $this->assertSame(0, AiUsageLog::count());
    }
}
