<?php

namespace Tests\Unit;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Services\NutritionCalculator;
use Tests\TestCase;

class NutritionCalculatorTest extends TestCase
{
    private NutritionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new NutritionCalculator;
    }

    public function test_energi_istirahat_memakai_mifflin_st_jeor(): void
    {
        $this->assertSame(1617.5, $this->calculator->restingEnergy(Gender::Male, 30, 170, 70));
        $this->assertSame(1451.5, $this->calculator->restingEnergy(Gender::Female, 30, 170, 70));
    }

    public function test_target_dewasa_dihitung_dari_tdee_dan_pembagian_makro(): void
    {
        $targets = $this->calculator->targets(
            Gender::Male, 30, 170, 70, ActivityLevel::Moderate, Goal::Maintain,
        );

        $this->assertSame(2507, $targets['calories_kcal']);
        $this->assertSame(125.4, $targets['protein_g']);
        $this->assertSame(69.6, $targets['fat_g']);
        $this->assertSame(344.7, $targets['carbs_g']);
        $this->assertSame(2000, $targets['water_ml']);
        $this->assertSame(1618, $targets['resting_kcal']);
        $this->assertSame(2507, $targets['tdee_kcal']);
    }

    public function test_remaja_tidak_diberi_pengurangan_kalori(): void
    {
        $targets = $this->calculator->targets(
            Gender::Female, 15, 160, 55, ActivityLevel::Sedentary, Goal::Lose,
        );

        // tdee = (10*55 + 6.25*160 - 5*15 - 161) * 1.2 = 1576.8
        $this->assertSame(1577, $targets['calories_kcal']);
    }

    public function test_bmi_dan_kategorinya(): void
    {
        $bmi = $this->calculator->bmi(65, 170);

        $this->assertSame(22.5, $bmi);
        $this->assertSame('Kurus', $this->calculator->bmiCategory(18.4, 20));
        $this->assertSame('Normal', $this->calculator->bmiCategory(18.5, 20));
        $this->assertSame('Normal', $this->calculator->bmiCategory(25.0, 20));
        $this->assertSame('Berat badan lebih', $this->calculator->bmiCategory(25.1, 20));
        $this->assertSame('Berat badan lebih', $this->calculator->bmiCategory(27.0, 20));
        $this->assertSame('Obesitas', $this->calculator->bmiCategory(27.1, 20));
    }

    public function test_bmi_remaja_tidak_diberi_label(): void
    {
        $this->assertNull($this->calculator->bmiCategory(22.5, 17));
    }

    public function test_kalori_dianggap_konsisten_dengan_makro(): void
    {
        $this->assertTrue($this->calculator->caloriesLookConsistent(418, 10, 60, 15));
        $this->assertTrue($this->calculator->caloriesLookConsistent(130, 25, 0, 0));
        $this->assertFalse($this->calculator->caloriesLookConsistent(131, 25, 0, 0));
        $this->assertFalse($this->calculator->caloriesLookConsistent(200, 10, 60, 15));
        $this->assertTrue($this->calculator->caloriesLookConsistent(10, 1, 1, 0.5));
    }
}
