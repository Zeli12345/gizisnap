<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_status_onboarding(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOnboarded());

        $user->update(['onboarded_at' => now()]);
        $this->assertTrue($user->fresh()->isOnboarded());

        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
    }

    public function test_target_berlaku_memakai_baris_terbaru_sebelum_tanggal(): void
    {
        $user = $this->onboardedUser();

        $user->dailyTargets()->create([
            'calories_kcal' => 1800,
            'protein_g' => 90,
            'carbs_g' => 240,
            'fat_g' => 50,
            'water_ml' => 1800,
            'source' => 'manual',
            'effective_from' => now()->subDays(3)->toDateString(),
        ]);

        $user->dailyTargets()->create([
            'calories_kcal' => 2200,
            'protein_g' => 110,
            'carbs_g' => 300,
            'fat_g' => 60,
            'water_ml' => 2500,
            'source' => 'manual',
            'effective_from' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame(2000, $user->targetFor(now())->calories_kcal);
        $this->assertSame(2200, $user->targetFor(now()->addDays(2))->calories_kcal);
    }

    public function test_nama_panggilan_dan_inisial(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);

        $this->assertSame('Budi', $user->firstName());
        $this->assertSame('BS', $user->initials());
        $this->assertSame('B', User::factory()->create(['name' => 'budi'])->initials());
    }
}
