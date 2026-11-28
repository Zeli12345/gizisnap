<?php

namespace Tests\Unit;

use App\Services\MealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealServiceTest extends TestCase
{
    use RefreshDatabase;

    private MealService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MealService::class);
    }

    public function test_nilai_item_dibersihkan_dan_dibatasi(): void
    {
        $user = $this->onboardedUser();

        $meal = $this->service->create($user, [
            'meal_type' => 'lunch',
            'eaten_at' => now(),
            'title' => 'Uji',
            'source' => 'manual',
        ], [[
            'name' => '  Nasi  ',
            'portion_desc' => '   ',
            'weight_g' => null,
            'calories_kcal' => -50,
            'protein_g' => 4.26,
            'carbs_g' => '10',
            'fat_g' => null,
            'fiber_g' => null,
            'sugar_g' => null,
            'sodium_mg' => null,
        ]]);

        $item = $meal->items->first();

        $this->assertSame('Nasi', $item->name);
        $this->assertSame('1 porsi', $item->portion_desc);
        $this->assertNull($item->weight_g);
        $this->assertSame(0.0, $item->calories_kcal);
        $this->assertSame(4.3, $item->protein_g);
        $this->assertSame(10.0, $item->carbs_g);
        $this->assertSame(0.0, $item->fat_g);
        $this->assertSame(0.0, $meal->total_calories_kcal);
        $this->assertSame(4.3, $meal->total_protein_g);
    }

    public function test_update_mengganti_item_lama_dan_menghitung_ulang(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);
        $this->assertSame(1, $meal->items()->count());

        $this->service->update($meal, [
            'title' => 'Menu baru',
            'meal_type' => 'dinner',
            'eaten_at' => now(),
            'notes' => 'catatan',
        ], [
            [
                'name' => 'Bakso',
                'portion_desc' => '1 mangkuk',
                'weight_g' => 250,
                'calories_kcal' => 300,
                'protein_g' => 15,
                'carbs_g' => 30,
                'fat_g' => 12,
            ],
            [
                'name' => 'Es teh',
                'portion_desc' => '1 gelas',
                'weight_g' => 250,
                'calories_kcal' => 90,
                'protein_g' => 0,
                'carbs_g' => 22,
                'fat_g' => 0,
            ],
        ]);

        $meal = $meal->fresh();

        $this->assertSame('Menu baru', $meal->title);
        $this->assertSame('dinner', $meal->meal_type->value);
        $this->assertSame(2, $meal->items()->count());
        $this->assertSame(390.0, $meal->total_calories_kcal);
        $this->assertSame(15.0, $meal->total_protein_g);
        $this->assertSame(52.0, $meal->total_carbs_g);
        $this->assertFalse($meal->items()->where('name', 'Nasi putih')->exists());
    }

    public function test_flag_koreksi_dipertahankan(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);

        $this->service->update($meal, [
            'title' => 'Menu',
            'meal_type' => 'lunch',
            'eaten_at' => now(),
        ], [[
            'name' => 'Nasi putih',
            'portion_desc' => '1 porsi',
            'weight_g' => 200,
            'calories_kcal' => 360,
            'protein_g' => 6.8,
            'carbs_g' => 79.2,
            'fat_g' => 0.6,
            'is_edited' => true,
        ]]);

        $this->assertTrue($meal->fresh()->items->first()->is_edited);
    }
}
