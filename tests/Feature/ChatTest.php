<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_chat_tampil(): void
    {
        $this->actingAs($this->onboardedUser())
            ->get('/chat')
            ->assertOk()
            ->assertSee('Camilan sehat');
    }

    public function test_percakapan_baru_dengan_pesan_pertama(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/chat', ['message' => 'Apa menu sehat hari ini?'])
            ->assertRedirect();

        $conversation = ChatConversation::firstWhere('user_id', $user->id);
        $this->assertNotNull($conversation);
        $this->assertSame('Apa menu sehat hari ini?', $conversation->title);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertSame('user', $conversation->messages()->first()->role);
        $this->assertSame('assistant', $conversation->messages()->latest('id')->first()->role);
        $this->assertStringContainsString('mode tiruan', $conversation->messages()->latest('id')->first()->content);
    }

    public function test_permintaan_json_mengembalikan_percakapan_dan_pesan(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->postJson('/chat', ['message' => 'Halo'])
            ->assertOk()
            ->assertJsonStructure([
                'conversation' => ['id', 'title', 'url', 'send_url', 'retry_url'],
                'user' => ['id', 'role', 'content', 'html', 'time'],
                'assistant' => ['id', 'role', 'content', 'html', 'time'],
            ])
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('assistant.role', 'assistant');
    }

    public function test_pesan_lanjutan_ditambahkan_ke_percakapan(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);

        $this->actingAs($user)
            ->postJson('/chat/'.$conversation->id.'/messages', ['message' => 'Lanjut'])
            ->assertOk();

        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_kirim_ulang_pesan_yang_belum_dijawab(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Halo']);

        $this->actingAs($user)
            ->postJson('/chat/'.$conversation->id.'/retry')
            ->assertOk()
            ->assertJsonPath('assistant.role', 'assistant');

        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_kirim_ulang_tanpa_pesan_tertunda_ditolak(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Sudah dijawab']);

        $this->actingAs($user)
            ->postJson('/chat/'.$conversation->id.'/retry')
            ->assertStatus(409);
    }

    public function test_percakapan_hanya_bisa_dibuka_pemiliknya(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $conversation = $this->makeConversation($owner);

        $this->actingAs($owner)->get('/chat/'.$conversation->id)->assertOk();
        $this->actingAs($other)->get('/chat/'.$conversation->id)->assertForbidden();
        $this->actingAs($other)
            ->postJson('/chat/'.$conversation->id.'/messages', ['message' => 'Halo'])
            ->assertForbidden();
        $this->actingAs($other)->delete('/chat/'.$conversation->id)->assertForbidden();
    }

    public function test_percakapan_bisa_dihapus(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);

        $this->actingAs($user)
            ->delete('/chat/'.$conversation->id)
            ->assertRedirect(route('chat.index'));

        $this->assertNull(ChatConversation::find($conversation->id));
    }

    public function test_validasi_pesan(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->postJson('/chat', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        $this->actingAs($user)
            ->postJson('/chat', ['message' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_kuota_chat_habis_mengembalikan_429(): void
    {
        $user = $this->onboardedUser();
        config(['gizisnap.ai.daily_chat_limit' => 1]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'chat',
            'model' => 'fake',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/chat', ['message' => 'Halo'])
            ->assertStatus(429)
            ->assertJsonPath('reason', 'quota')
            ->assertJsonPath('conversation', null);

        $this->assertDatabaseCount('chat_conversations', 0);
    }

    public function test_kuota_habis_pada_percakapan_lama_tidak_menambah_pesan(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Jawaban lama']);
        config(['gizisnap.ai.daily_chat_limit' => 1]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'chat',
            'model' => 'fake',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/chat/'.$conversation->id.'/messages', ['message' => 'Halo'])
            ->assertStatus(429);

        $this->assertSame(1, $conversation->messages()->count());
        $this->assertNotNull(ChatConversation::find($conversation->id));
    }

    public function test_pesan_dari_pengguna_lain_tidak_masuk_sidebar(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $this->makeConversation($other, ['title' => 'Punya orang lain']);

        $this->actingAs($owner)
            ->get('/chat')
            ->assertOk()
            ->assertDontSee('Punya orang lain');
    }
}
