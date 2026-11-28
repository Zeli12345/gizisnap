<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'height_cm' => 170,
            'weight_kg' => 65,
            'activity_level' => 'moderate',
            'goal' => 'maintain',
            'allergies' => 'kacang, udang, kacang',
            'dietary_preference' => 'Tanpa daging',
            'health_conditions' => 'Maag',
        ], $overrides);
    }

    public function test_tamu_dan_pengguna_onboarded_tidak_bisa_membuka_onboarding(): void
    {
        $this->get('/onboarding')->assertRedirect(route('login'));

        $this->actingAs($this->onboardedUser())
            ->get('/onboarding')
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_dialihkan_dari_onboarding(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/onboarding')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->post('/onboarding', $this->payload())
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseCount('health_profiles', 0);
    }

    public function test_onboarding_menyimpan_profil_target_dan_berat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding', $this->payload())
            ->assertRedirect(route('dashboard', ['welcome' => 1]));

        $user = $user->fresh();

        $this->assertNotNull($user->onboarded_at);
        $this->assertNotNull($user->healthProfile);
        $this->assertSame(['kacang', 'udang'], $user->healthProfile->allergies);
        $this->assertSame('male', $user->healthProfile->gender->value);
        $this->assertSame(170.0, $user->healthProfile->height_cm);

        $target = $user->targetFor(now());
        $this->assertNotNull($target);
        $this->assertGreaterThan(1000, $target->calories_kcal);
        $this->assertSame('auto', $target->source);

        $this->assertDatabaseHas('weight_logs', [
            'user_id' => $user->id,
            'weight_kg' => 65,
        ]);
    }

    public function test_usia_minimal_tiga_belas_tahun(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding', $this->payload([
                'birth_date' => now()->subYears(10)->toDateString(),
            ]))
            ->assertSessionHasErrors('birth_date');

        $this->assertNull($user->fresh()->onboarded_at);
    }

    public function test_remaja_tidak_boleh_memilih_turunkan_berat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding', $this->payload([
                'birth_date' => now()->subYears(15)->toDateString(),
                'goal' => 'lose',
            ]))
            ->assertSessionHasErrors([
                'goal' => 'Untuk usia di bawah 18 tahun, pilih menjaga atau menaikkan berat.',
            ]);

        $this->assertDatabaseCount('health_profiles', 0);
    }

    public function test_data_wajib_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding', [])
            ->assertSessionHasErrors(['gender', 'birth_date', 'height_cm', 'weight_kg', 'activity_level', 'goal']);
    }

    public function test_alergi_kosong_disimpan_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding', $this->payload(['allergies' => '  ,  ']))
            ->assertRedirect(route('dashboard', ['welcome' => 1]));

        $this->assertNull($user->fresh()->healthProfile->allergies);
    }
}
