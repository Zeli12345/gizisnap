<?php

namespace Tests\Feature;

use App\Enums\MealType;
use App\Exceptions\AiFailure;
use App\Models\AiUsageLog;
use App\Models\Food;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendingScan($user, ?string $path = null): Scan
    {
        return Scan::create([
            'user_id' => $user->id,
            'image_path' => $path ?? 'scans/'.$user->id.'/uji.jpg',
            'status' => 'pending',
        ]);
    }

    public function test_analisis_tiruan_menyimpan_hasil_dan_menambah_katalog(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->pendingScan($user);

        $result = app(ScanService::class)->analyze($scan, 'nasi.jpg');

        $this->assertSame('completed', $result->status->value);
        $this->assertSame('Nasi goreng telur', $result->dish_name);
        $this->assertSame(0.86, $result->confidence);
        $this->assertCount(3, $result->items());
        $this->assertSame(3, Food::count());

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'scan',
            'status' => 'success',
        ]);
    }

    public function test_foto_bukan_makanan_ditandai_not_food(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->pendingScan($user);

        $result = app(ScanService::class)->analyze($scan, 'bukan-makanan.jpg');

        $this->assertSame('not_food', $result->status->value);
        $this->assertNull($result->dish_name);
        $this->assertSame([], $result->items());
        $this->assertSame(0, Food::count());
    }

    public function test_kegagalan_ai_dicatat_pada_scan_dan_log(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->pendingScan($user);

        $result = app(ScanService::class)->analyze($scan, 'error.jpg');

        $this->assertSame('failed', $result->status->value);
        $this->assertSame('Analisis gagal. Coba lagi.', $result->error_message);
        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'scan',
            'status' => 'error',
            'error_code' => 'error',
        ]);
    }

    public function test_kuota_scan_habis_sebelum_foto_disimpan(): void
    {
        $user = $this->onboardedUser();
        config(['gizisnap.ai.daily_scan_limit' => 1]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'scan',
            'model' => 'fake',
            'status' => 'success',
            'created_at' => now(),
        ]);

        try {
            app(ScanService::class)->createAndAnalyze($user, $this->fakeJpeg());
            $this->fail('Kuota seharusnya habis.');
        } catch (AiFailure $e) {
            $this->assertSame('quota', $e->reason);
        }

        $this->assertSame(0, Scan::count());
    }

    public function test_hasil_scan_disimpan_sebagai_makanan_tanpa_koreksi(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);

        $meal = app(ScanService::class)->saveAsMeal(
            $scan,
            MealType::Lunch,
            now(),
            'Nasi goreng telur',
            [[
                'name' => 'Nasi goreng',
                'portion_desc' => '1 piring',
                'weight_g' => 250,
                'calories_kcal' => 418,
                'protein_g' => 10,
                'carbs_g' => 60,
                'fat_g' => 15,
            ]],
        );

        $this->assertSame($scan->id, $meal->scan_id);
        $this->assertSame('scan', $meal->source);
        $this->assertSame(418.0, $meal->total_calories_kcal);
        $this->assertFalse($meal->items->first()->is_edited);
    }

    public function test_koreksi_nilai_gizi_ditandai_is_edited(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);

        $meal = app(ScanService::class)->saveAsMeal(
            $scan,
            MealType::Lunch,
            now(),
            'Nasi goreng telur',
            [[
                'name' => 'Nasi goreng',
                'portion_desc' => '1 piring',
                'weight_g' => 250,
                'calories_kcal' => 500,
                'protein_g' => 10,
                'carbs_g' => 60,
                'fat_g' => 15,
            ]],
        );

        $this->assertTrue($meal->items->first()->is_edited);
    }
}
