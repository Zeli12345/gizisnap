<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DailySummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailySummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private DailySummaryService $summary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->summary = app(DailySummaryService::class);
    }

    public function test_ringkasan_harian_menghitung_total_sisa_dan_air(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['eaten_at' => now()->setTime(8, 0)]);
        $this->makeMeal($user, ['eaten_at' => now()->setTime(12, 0)]);
        $user->waterLogs()->create(['amount_ml' => 500, 'logged_at' => now()]);

        $summary = $this->summary->forDate($user, now());

        $this->assertSame(720.0, $summary['totals']['calories_kcal']);
        $this->assertSame(13.6, $summary['totals']['protein_g']);
        $this->assertSame(1280, $summary['remaining_kcal']);
        $this->assertSame(500, $summary['water_ml']);
        $this->assertSame(2000, $summary['water_target_ml']);
        $this->assertCount(2, $summary['meals']);
    }

    public function test_seri_harian_selalu_sepanjang_periode(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);

        $series = $this->summary->series($user, 7);

        $this->assertCount(7, $series);
        $this->assertTrue($series->last()['logged']);
        $this->assertSame(360.0, $series->last()['calories_kcal']);
        $this->assertSame(2000, $series->last()['target_kcal']);
        $this->assertFalse($series->first()['logged']);
    }

    public function test_statistik_periode(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['eaten_at' => now()]);
        $this->makeMeal($user, ['eaten_at' => now()->subDay()], [[
            'name' => 'Ayam', 'calories_kcal' => 1640, 'protein_g' => 93.2, 'carbs_g' => 195.8, 'fat_g' => 54.4,
        ]]);

        $series = $this->summary->series($user, 7);
        $stats = $this->summary->periodStats($series);

        $this->assertSame(7, $stats['days']);
        $this->assertSame(2, $stats['logged_days']);
        $this->assertSame(1000, $stats['avg_calories']);
        $this->assertSame(50.0, $stats['avg_protein']);
        // Hari pertama: 360/2000 = 18%, hari kedua: 1640/2000 = 82%.
        $this->assertSame(0, $stats['within_target_days']);
    }

    public function test_streak_berhenti_saat_ada_hari_kosong(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 10:00:00'), function () {
            $user = $this->onboardedUser();

            $this->makeMeal($user, ['eaten_at' => now()]);
            $this->makeMeal($user, ['eaten_at' => now()->subDay()]);
            $this->makeMeal($user, ['eaten_at' => now()->subDays(2)]);
            $this->makeMeal($user, ['eaten_at' => now()->subDays(4)]);

            $this->assertSame(3, $this->summary->streak($user));
        });
    }

    public function test_streak_tetap_dihitung_bila_hari_ini_belum_mencatat(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 10:00:00'), function () {
            $user = $this->onboardedUser();

            $this->makeMeal($user, ['eaten_at' => now()->subDay()]);

            $this->assertSame(1, $this->summary->streak($user));
        });
    }

    public function test_konteks_chat_memuat_profil_dan_asupan(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['title' => 'Nasi putih']);

        $context = $this->summary->contextForChat($user);

        $this->assertStringContainsString('Target harian: 2000 kkal', $context);
        $this->assertStringContainsString('Sisa kalori hari ini: 1640 kkal', $context);
        $this->assertStringContainsString('Nasi putih', $context);
        $this->assertStringContainsString('Laki-laki', $context);
    }

    public function test_pengguna_tanpa_profil_tetap_mendapat_konteks(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);

        $context = $this->summary->contextForChat($user);

        $this->assertStringContainsString('Nama panggilan: Budi', $context);
        $this->assertStringContainsString('belum ada catatan', $context);
    }
}
