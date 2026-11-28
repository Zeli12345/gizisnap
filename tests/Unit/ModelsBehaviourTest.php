<?php

namespace Tests\Unit;

use App\Models\HealthProfile;
use App\Models\NutritionReport;
use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelsBehaviourTest extends TestCase
{
    use RefreshDatabase;

    public function test_umur_dan_status_remaja_profil(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 10:00:00'), function () {
            $adult = HealthProfile::factory()->create([
                'birth_date' => '2000-11-28',
            ]);
            $teen = HealthProfile::factory()->minor()->create([
                'birth_date' => '2011-11-28',
            ]);
            $almostAdult = HealthProfile::factory()->minor()->create([
                'birth_date' => '2008-11-29',
            ]);

            $this->assertSame(26, $adult->age());
            $this->assertFalse($adult->isMinor());
            $this->assertSame(15, $teen->age());
            $this->assertTrue($teen->isMinor());
            $this->assertSame(17, $almostAdult->age());
            $this->assertTrue($almostAdult->isMinor());
        });
    }

    public function test_scan_menjumlah_item_dan_menandai_keyakinan_rendah(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user, [
            'confidence' => 0.4,
            'ai_result' => [
                'is_food' => true,
                'items' => [
                    ['name' => 'Nasi', 'calories_kcal' => 200.5, 'protein_g' => 4.2, 'carbs_g' => 40, 'fat_g' => 1],
                    ['name' => 'Ayam', 'calories_kcal' => 150.5, 'protein_g' => 20, 'carbs_g' => 0, 'fat_g' => 8],
                ],
            ],
        ]);

        $this->assertCount(2, $scan->items());
        $this->assertSame([
            'calories_kcal' => 351.0,
            'protein_g' => 24.2,
            'carbs_g' => 40.0,
            'fat_g' => 9.0,
        ], $scan->totals());
        $this->assertTrue($scan->isLowConfidence());

        $scan->update(['confidence' => 0.86]);
        $this->assertFalse($scan->fresh()->isLowConfidence());

        $scan->update(['confidence' => null]);
        $this->assertFalse($scan->fresh()->isLowConfidence());
    }

    public function test_scan_tanpa_hasil_mengembalikan_item_kosong(): void
    {
        $scan = new Scan(['ai_result' => null]);

        $this->assertSame([], $scan->items());
        $this->assertSame([
            'calories_kcal' => 0.0,
            'protein_g' => 0.0,
            'carbs_g' => 0.0,
            'fat_g' => 0.0,
        ], $scan->totals());
    }

    public function test_status_laporan_siap(): void
    {
        $user = $this->onboardedUser();

        $ready = NutritionReport::create([
            'user_id' => $user->id,
            'period_days' => 7,
            'period_start' => now()->subDays(6)->toDateString(),
            'period_end' => today()->toDateString(),
            'fingerprint' => 'abc',
            'status' => 'ready',
            'content' => ['headline' => 'Bagus'],
            'generated_at' => now(),
        ]);

        $this->assertTrue($ready->isReady());
        $this->assertSame(['headline' => 'Bagus'], $ready->content);

        $ready->update(['content' => null]);
        $this->assertFalse($ready->fresh()->isReady());

        $failed = NutritionReport::create([
            'user_id' => $user->id,
            'period_days' => 7,
            'period_start' => now()->subDays(6)->toDateString(),
            'period_end' => now()->subDay()->toDateString(),
            'fingerprint' => 'def',
            'status' => 'failed',
            'content' => ['headline' => 'Ada isi'],
            'generated_at' => now(),
        ]);

        $this->assertFalse($failed->isReady());
    }

    public function test_total_makanan_dihitung_ulang_dari_item(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);

        $this->assertSame(360.0, $meal->total_calories_kcal);

        $meal->items()->create([
            'name' => 'Ayam bakar',
            'portion_desc' => '1 potong',
            'weight_g' => 100,
            'calories_kcal' => 226.2,
            'protein_g' => 27,
            'carbs_g' => 0,
            'fat_g' => 13,
            'fiber_g' => 0,
            'sugar_g' => 0,
            'sodium_mg' => 500,
        ]);

        $meal->recalculateTotals();
        $meal->refresh();

        $this->assertSame(586.2, $meal->total_calories_kcal);
        $this->assertSame(33.8, $meal->total_protein_g);
        $this->assertSame(502.0, $meal->total_sodium_mg);
    }

    public function test_tanggal_disimpan_sebagai_y_m_d(): void
    {
        $user = $this->onboardedUser();

        $user->dailyTargets()->create([
            'calories_kcal' => 2100,
            'protein_g' => 105,
            'carbs_g' => 288,
            'fat_g' => 58,
            'water_ml' => 2200,
            'source' => 'manual',
            'effective_from' => Carbon::parse('2026-12-01 10:30:00'),
        ]);

        $this->assertSame(
            '2026-12-01',
            DB::table('daily_targets')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->value('effective_from')
        );

        $log = $user->weightLogs()->create([
            'weight_kg' => 64,
            'logged_on' => Carbon::parse('2026-11-20 22:00:00'),
        ]);

        $this->assertSame(
            '2026-11-20',
            DB::table('weight_logs')->where('id', $log->id)->value('logged_on')
        );
    }
}
