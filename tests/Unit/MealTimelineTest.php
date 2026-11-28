<?php

namespace Tests\Unit;

use App\Services\MealTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MealTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_catatan_berdekatan_digabung_menjadi_satu_sesi(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 18:00:00'), function () {
            $user = $this->onboardedUser();

            $this->makeMeal($user, ['eaten_at' => now()->setTime(8, 0), 'title' => 'Nasi putih'], [
                ['name' => 'Nasi', 'calories_kcal' => 200, 'protein_g' => 4, 'carbs_g' => 40, 'fat_g' => 1],
            ]);
            $this->makeMeal($user, ['eaten_at' => now()->setTime(8, 30), 'title' => 'Telur'], [
                ['name' => 'Telur', 'calories_kcal' => 90, 'protein_g' => 7, 'carbs_g' => 1, 'fat_g' => 6],
            ]);
            $this->makeMeal($user, ['eaten_at' => now()->setTime(12, 0), 'title' => 'Ayam'], [
                ['name' => 'Ayam', 'calories_kcal' => 250, 'protein_g' => 30, 'carbs_g' => 0, 'fat_g' => 12],
            ]);

            $timeline = app(MealTimeline::class)->forDate($user, now());

            $this->assertCount(2, $timeline);

            $this->assertSame('Nasi putih + Telur', $timeline[0]['label']);
            $this->assertSame('Pagi', $timeline[0]['part_of_day']);
            $this->assertSame(290.0, $timeline[0]['calories']);
            $this->assertCount(2, $timeline[0]['meals']);

            $this->assertSame('Ayam', $timeline[1]['label']);
            $this->assertSame('Siang', $timeline[1]['part_of_day']);
            $this->assertSame(250.0, $timeline[1]['calories']);
        });
    }

    public function test_hari_tanpa_catatan_mengembalikan_array_kosong(): void
    {
        $user = $this->onboardedUser();

        $this->assertSame([], app(MealTimeline::class)->forDate($user, now()->subWeek()));
    }

    public function test_label_sesi_memakai_tiga_judul_pertama_lalu_ringkasan(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 21:00:00'), function () {
            $user = $this->onboardedUser();

            foreach ([['A', 8, 0], ['B', 8, 5], ['C', 8, 10], ['D', 8, 15]] as [$title, $hour, $minute]) {
                $this->makeMeal($user, [
                    'eaten_at' => now()->setTime($hour, $minute),
                    'title' => $title,
                ], [['name' => $title, 'calories_kcal' => 10]]);
            }

            $timeline = app(MealTimeline::class)->forDate($user, now());

            $this->assertCount(1, $timeline);
            $this->assertSame('A + B +2', $timeline[0]['label']);
        });
    }
}
