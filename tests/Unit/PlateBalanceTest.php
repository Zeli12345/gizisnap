<?php

namespace Tests\Unit;

use App\Enums\FoodGroup;
use App\Services\PlateBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlateBalanceTest extends TestCase
{
    use RefreshDatabase;

    private PlateBalance $plate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plate = app(PlateBalance::class);
    }

    public function test_piring_lengkap_empat_kelompok(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, [], [
            ['name' => 'Nasi putih', 'weight_g' => 200, 'calories_kcal' => 360],
            ['name' => 'Ayam bakar', 'weight_g' => 100, 'calories_kcal' => 226],
            ['name' => 'Kangkung tumis', 'weight_g' => 100, 'calories_kcal' => 54],
            ['name' => 'Apel', 'weight_g' => 100, 'calories_kcal' => 52],
        ]);

        $result = $this->plate->forDate($user, now());

        $this->assertTrue($result['is_balanced']);
        $this->assertSame(4, $result['covered']);
        $this->assertSame(4, $result['total']);
        $this->assertSame([], $result['missing']);
        $this->assertSame('Piring hari ini sudah lengkap empat kelompoknya.', $result['headline']);
    }

    public function test_piring_kosong_dan_belum_lengkap(): void
    {
        $user = $this->onboardedUser();

        $empty = $this->plate->forDate($user, now());
        $this->assertFalse($empty['is_balanced']);
        $this->assertSame(0, $empty['covered']);
        $this->assertSame('Piring masih kosong. Mulai dari satu makanan pokok.', $empty['headline']);

        $this->makeMeal($user, [], [
            ['name' => 'Nasi putih', 'weight_g' => 200, 'calories_kcal' => 360],
        ]);

        $partial = $this->plate->forDate($user, now());
        $this->assertFalse($partial['is_balanced']);
        $this->assertSame(1, $partial['covered']);
        $this->assertContains(FoodGroup::Protein, $partial['missing']);
        $this->assertContains(FoodGroup::Vegetable, $partial['missing']);
        $this->assertContains(FoodGroup::Fruit, $partial['missing']);
        $this->assertSame('Piringmu baru terisi 1 dari 4 kelompok. Kurang protein, sayur, buah.', $partial['headline']);
    }

    public function test_hampir_lengkap_format_pesan_berbeda(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, [], [
            ['name' => 'Nasi putih', 'weight_g' => 200, 'calories_kcal' => 360],
            ['name' => 'Ayam bakar', 'weight_g' => 100, 'calories_kcal' => 226],
            ['name' => 'Kangkung tumis', 'weight_g' => 100, 'calories_kcal' => 54],
        ]);

        $result = $this->plate->forDate($user, now());

        $this->assertSame(3, $result['covered']);
        $this->assertSame('Hampir lengkap. Tinggal tambah buah.', $result['headline']);
    }

    public function test_berat_kosong_dianggap_seratus_gram(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, [], [
            ['name' => 'Apel', 'weight_g' => null, 'calories_kcal' => 52],
            ['name' => 'Tempe', 'weight_g' => null, 'calories_kcal' => 100],
            ['name' => 'Nasi', 'weight_g' => null, 'calories_kcal' => 200],
            ['name' => 'Sayur bayam', 'weight_g' => null, 'calories_kcal' => 30],
        ]);

        $result = $this->plate->forDate($user, now());

        $this->assertTrue($result['is_balanced']);
        $fruit = collect($result['groups'])->firstWhere('group', FoodGroup::Fruit);
        $this->assertSame(100.0, $fruit['grams']);
    }

    public function test_keseimbangan_sepanjang_periode(): void
    {
        $this->travelTo(now(), function () {
            $user = $this->onboardedUser();

            $this->makeMeal($user, ['eaten_at' => now()], [
                ['name' => 'Nasi', 'weight_g' => 200, 'calories_kcal' => 300],
                ['name' => 'Ayam', 'weight_g' => 100, 'calories_kcal' => 200],
                ['name' => 'Sayur', 'weight_g' => 100, 'calories_kcal' => 50],
                ['name' => 'Apel', 'weight_g' => 100, 'calories_kcal' => 50],
            ]);

            $series = app(\App\Services\DailySummaryService::class)->series($user, 7);
            $balance = $this->plate->balanceOverPeriod($user, $series);

            $this->assertSame(7, $balance['days']);
            $this->assertSame(1, $balance['balanced']);
            $this->assertSame(14, $balance['rate']);
        });
    }
}
