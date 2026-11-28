<?php

namespace Tests\Unit;

use App\Models\Food;
use App\Services\FoodCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_makanan_hasil_scan_disalin_ke_katalog(): void
    {
        $added = app(FoodCatalog::class)->addFromScan([
            [
                'name' => '  Nasi goreng  ',
                'portion_desc' => '1 piring',
                'weight_g' => 250,
                'calories_kcal' => 418,
                'protein_g' => 10,
                'carbs_g' => 60,
                'fat_g' => 15,
                'fiber_g' => 2,
                'sugar_g' => 3,
                'sodium_mg' => 820,
            ],
        ]);

        $this->assertSame(1, $added);

        $food = Food::firstWhere('name', 'Nasi goreng');
        $this->assertNotNull($food);
        $this->assertSame('Hasil scan', $food->category);
        $this->assertSame('Scan pengguna', $food->source);
        $this->assertTrue($food->is_active);
        $this->assertSame(250.0, $food->serving_weight_g);
        $this->assertSame(418.0, $food->calories_kcal);
        $this->assertSame(820.0, $food->sodium_mg);
    }

    public function test_nama_kosong_dan_duplikat_dilewati(): void
    {
        $this->makeFood(['name' => 'Nasi goreng']);

        $added = app(FoodCatalog::class)->addFromScan([
            ['name' => ''],
            ['name' => '   '],
            ['name' => 'nasi goreng'],
            ['name' => 'NASI GORENG'],
            ['name' => 'Bakso'],
        ]);

        $this->assertSame(1, $added);
        $this->assertSame(1, Food::whereRaw('LOWER(name) = ?', ['nasi goreng'])->count());
        $this->assertTrue(Food::where('name', 'Bakso')->exists());
    }

    public function test_berat_minimal_satu_gram_dan_nilai_negatif_jadi_nol(): void
    {
        app(FoodCatalog::class)->addFromScan([
            ['name' => 'Air', 'weight_g' => 0, 'calories_kcal' => -10, 'protein_g' => -1],
        ]);

        $food = Food::firstWhere('name', 'Air');
        $this->assertSame(1.0, $food->serving_weight_g);
        $this->assertSame(0.0, $food->calories_kcal);
        $this->assertSame(0.0, $food->protein_g);
    }
}
