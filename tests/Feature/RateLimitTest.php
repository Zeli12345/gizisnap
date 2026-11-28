<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_permintaan_ai_per_menit_dibatasi_per_pengguna(): void
    {
        config(['gizisnap.ai.per_minute_limit' => 2]);

        $user = $this->onboardedUser();

        $this->actingAs($user)->postJson('/chat', ['message' => 'Satu'])->assertOk();
        $this->actingAs($user)->postJson('/chat', ['message' => 'Dua'])->assertOk();

        $this->actingAs($user)
            ->postJson('/chat', ['message' => 'Tiga'])
            ->assertStatus(429)
            ->assertJson(['message' => 'Terlalu banyak permintaan. Tunggu satu menit lalu coba lagi.']);

        // Pengguna lain punya kuota per menit sendiri.
        $other = $this->onboardedUser();
        $this->actingAs($other)->postJson('/chat', ['message' => 'Halo'])->assertOk();
    }

    public function test_batas_per_menit_memakai_nilai_config(): void
    {
        config(['gizisnap.ai.per_minute_limit' => 1]);

        $user = $this->onboardedUser();

        $this->actingAs($user)->postJson('/chat', ['message' => 'Satu'])->assertOk();

        $this->actingAs($user)
            ->postJson('/chat', ['message' => 'Dua'])
            ->assertStatus(429);
    }
}
