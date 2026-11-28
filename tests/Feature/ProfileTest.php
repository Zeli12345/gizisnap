<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Baru',
            'gender' => 'male',
            'birth_date' => now()->subYears(21)->toDateString(),
            'height_cm' => 172,
            'weight_kg' => 70,
            'activity_level' => 'active',
            'goal' => 'gain',
            'allergies' => 'kacang, susu',
            'dietary_preference' => 'Tanpa daging',
            'health_conditions' => 'Asam lambung',
        ], $overrides);
    }

    public function test_profil_pengguna_dan_admin_tampil(): void
    {
        $this->actingAs($this->onboardedUser())->get('/profile')->assertOk();

        $this->actingAs($this->adminUser())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Akun admin');
    }

    public function test_profil_diubah_dan_target_otomatis_ikut_dihitung(): void
    {
        $user = $this->onboardedUser();
        $oldCalories = $user->targetFor(now())->calories_kcal;

        $this->actingAs($user)
            ->put('/profile', $this->payload())
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Budi Baru', $user->name);
        $this->assertSame(172.0, $user->healthProfile->height_cm);
        $this->assertSame(70.0, $user->healthProfile->weight_kg);
        $this->assertSame(['kacang', 'susu'], $user->healthProfile->allergies);
        $this->assertSame('Asam lambung', $user->healthProfile->health_conditions);

        $target = $user->targetFor(now());
        $this->assertSame('auto', $target->source);
        $this->assertNotSame($oldCalories, $target->calories_kcal);

        $this->assertDatabaseHas('weight_logs', [
            'user_id' => $user->id,
            'weight_kg' => 70,
        ]);
    }

    public function test_target_manual_tidak_ditimpa_saat_profil_diubah(): void
    {
        $user = $this->onboardedUser();
        $user->dailyTargets()->update([
            'calories_kcal' => 2100,
            'protein_g' => 105,
            'carbs_g' => 288,
            'fat_g' => 58,
            'water_ml' => 2200,
            'source' => 'manual',
        ]);

        $this->actingAs($user)->put('/profile', $this->payload())->assertSessionHas('success');

        $target = $user->fresh()->targetFor(now());
        $this->assertSame(2100, $target->calories_kcal);
        $this->assertSame('manual', $target->source);
    }

    public function test_target_harian_bisa_diubah_manual(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->put('/profile/targets', [
                'calories_kcal' => 1800,
                'protein_g' => 90,
                'carbs_g' => 240,
                'fat_g' => 50,
                'water_ml' => 2500,
            ])
            ->assertSessionHas('success');

        $target = $user->fresh()->targetFor(now());
        $this->assertSame(1800, $target->calories_kcal);
        $this->assertSame(2500, $target->water_ml);
        $this->assertSame('manual', $target->source);
    }

    public function test_target_manual_bisa_dikembalikan_ke_otomatis(): void
    {
        $user = $this->onboardedUser();
        $user->dailyTargets()->update(['source' => 'manual']);

        $this->actingAs($user)
            ->delete('/profile/targets')
            ->assertSessionHas('success');

        $this->assertSame('auto', $user->fresh()->targetFor(now())->source);
    }

    public function test_validasi_target_dan_profil(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->put('/profile/targets', ['calories_kcal' => 100])
            ->assertSessionHasErrors(['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'water_ml']);

        $this->actingAs($user)
            ->put('/profile', $this->payload(['name' => '', 'height_cm' => 50]))
            ->assertSessionHasErrors(['name', 'height_cm']);
    }

    public function test_akun_dan_semua_data_bisa_dihapus(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['title' => 'Akan dihapus']);

        $this->actingAs($user)
            ->delete('/profile', ['confirmation' => 'HAPUS'])
            ->assertRedirect(route('landing'));

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertDatabaseCount('meals', 0);
    }

    public function test_konfirmasi_hapus_akun_harus_benar(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->delete('/profile', ['confirmation' => 'hapus'])
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(User::find($user->id));
        $this->assertAuthenticatedAs($user);
    }
}
