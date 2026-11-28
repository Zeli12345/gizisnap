<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_air_minum_bisa_dicatat_lewat_json(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->postJson('/water', ['amount_ml' => 250, 'vessel' => 'gelas'])
            ->assertOk()
            ->assertJson([
                'water_ml' => 250,
                'water_target_ml' => 2000,
            ]);

        $this->assertDatabaseHas('water_logs', [
            'user_id' => $user->id,
            'amount_ml' => 250,
            'vessel' => 'gelas',
        ]);
    }

    public function test_air_minum_lewat_formulir_biasa(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/water', ['amount_ml' => 600, 'vessel' => 'tumbler'])
            ->assertSessionHas('success');

        $this->assertSame(600, (int) $user->waterLogs()->sum('amount_ml'));
    }

    public function test_validasi_jumlah_dan_wadah_air(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/water', ['amount_ml' => 10])
            ->assertSessionHasErrors('amount_ml');

        $this->actingAs($user)
            ->post('/water', ['amount_ml' => 250, 'vessel' => 'ember'])
            ->assertSessionHasErrors('vessel');

        $this->assertDatabaseCount('water_logs', 0);
    }

    public function test_entri_air_terakhir_hari_ini_bisa_dibatalkan(): void
    {
        $user = $this->onboardedUser();

        $user->waterLogs()->create(['amount_ml' => 200, 'logged_at' => now()->subHour()]);
        $user->waterLogs()->create(['amount_ml' => 300, 'logged_at' => now()]);
        $user->waterLogs()->create(['amount_ml' => 500, 'logged_at' => now()->subDay()]);

        $this->actingAs($user)
            ->deleteJson('/water/latest')
            ->assertOk()
            ->assertJson(['water_ml' => 200]);

        $this->assertSame(1, $user->waterLogs()->whereDate('logged_at', today())->count());
        $this->assertSame(1, $user->waterLogs()->whereDate('logged_at', today()->subDay())->count());
    }

    public function test_membatalkan_tanpa_catatan_tetap_aman(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->deleteJson('/water/latest')
            ->assertOk()
            ->assertJson(['water_ml' => 0]);
    }
}
