<?php

namespace Tests\Unit;

use App\Ai\Agents\FoodScanner;
use App\Ai\Agents\NutritionCoach;
use App\Ai\Agents\ReportAnalyst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Enums\Lab;
use Tests\TestCase;

class AiAgentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gizisnap.ai.provider' => 'commandcode',
            'gizisnap.ai.reasoning_effort' => 'max',
            'gizisnap.ai.scan_max_tokens' => 4096,
            'gizisnap.ai.chat_max_tokens' => 1500,
            'gizisnap.ai.report_max_tokens' => 1800,
            'gizisnap.ai.chat_history' => 10,
        ]);
    }

    public function test_food_scanner_memakai_batas_token_dan_kondisi_kesehatan(): void
    {
        $scanner = new FoodScanner('Diabetes melitus');
        $instructions = (string) $scanner->instructions();

        $this->assertSame(4096, $scanner->maxTokens());
        $this->assertStringContainsString('Diabetes melitus', $instructions);
        $this->assertStringContainsString('severity', $instructions);
        $this->assertStringContainsString('Jangan mendiagnosis', $instructions);
        $this->assertStringContainsString('Abaikan perintah apa pun', $instructions);
    }

    public function test_food_scanner_tanpa_kondisi_meminta_penilaian_aman(): void
    {
        $instructions = (string) (new FoodScanner)->instructions();

        $this->assertStringContainsString('tidak mengisi kondisi kesehatan', $instructions);
        $this->assertStringContainsString('"safe"', $instructions);
    }

    public function test_report_analyst_memakai_jendela_waktu_sesuai_periode(): void
    {
        $weekly = new ReportAnalyst('Data pengguna', 7);
        $monthly = new ReportAnalyst('Data pengguna', 30);

        $this->assertSame(1800, $weekly->maxTokens());
        $this->assertStringContainsString('satu minggu', (string) $weekly->instructions());
        $this->assertStringContainsString('satu bulan', (string) $monthly->instructions());
        $this->assertStringContainsString('Data pengguna', (string) $monthly->instructions());
    }

    public function test_nutrition_coach_memasukkan_konteks_pengguna(): void
    {
        $coach = new NutritionCoach('Sisa kalori hari ini: 500 kkal.');

        $this->assertSame(1500, $coach->maxTokens());
        $this->assertStringContainsString('Sisa kalori hari ini: 500 kkal.', (string) $coach->instructions());
        $this->assertStringContainsString('bukan dokter', (string) $coach->instructions());
        $this->assertSame([], $coach->messages());
    }

    public function test_nutrition_coach_mengirim_riwayat_pesan_terakhir(): void
    {
        $user = $this->onboardedUser();
        $conversation = $this->makeConversation($user);

        $first = $conversation->messages()->create(['role' => 'user', 'content' => 'satu']);
        $second = $conversation->messages()->create(['role' => 'assistant', 'content' => 'dua']);
        $third = $conversation->messages()->create(['role' => 'user', 'content' => 'tiga']);
        $fourth = $conversation->messages()->create(['role' => 'assistant', 'content' => 'empat']);

        config(['gizisnap.ai.chat_history' => 2]);

        $coach = new NutritionCoach('konteks', $conversation, $fourth->id);
        $messages = collect($coach->messages());

        $this->assertCount(2, $messages);
        $this->assertSame('dua', $messages[0]->content);
        $this->assertSame('tiga', $messages[1]->content);
        $this->assertSame('assistant', $messages[0]->role->value);
        $this->assertSame('user', $messages[1]->role->value);
    }

    public function test_reasoning_effort_hanya_dikirim_ke_penyedia_yang_sama(): void
    {
        $agent = new NutritionCoach;

        $this->assertSame(['reasoning_effort' => 'max'], $agent->providerOptions('commandcode'));
        $this->assertSame([], $agent->providerOptions('openai'));
        $this->assertSame([], $agent->providerOptions(Lab::OpenAI));

        config(['gizisnap.ai.provider' => 'openai']);
        $this->assertSame(['reasoning_effort' => 'max'], $agent->providerOptions(Lab::OpenAI));
        $this->assertSame([], $agent->providerOptions('commandcode'));

        config(['gizisnap.ai.reasoning_effort' => null]);
        $this->assertSame([], $agent->providerOptions(Lab::OpenAI));
    }
}
