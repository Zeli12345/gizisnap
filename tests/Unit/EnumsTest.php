<?php

namespace Tests\Unit;

use App\Enums\ActivityLevel;
use App\Enums\FoodGroup;
use App\Enums\Gender;
use App\Enums\Goal;
use App\Enums\MealType;
use App\Enums\Role;
use App\Enums\ScanStatus;
use Tests\TestCase;

class EnumsTest extends TestCase
{
    public function test_meal_type_ditebak_dari_jam(): void
    {
        $this->assertSame(MealType::Breakfast, MealType::guessFromHour(4));
        $this->assertSame(MealType::Breakfast, MealType::guessFromHour(9));
        $this->assertSame(MealType::Lunch, MealType::guessFromHour(10));
        $this->assertSame(MealType::Lunch, MealType::guessFromHour(15));
        $this->assertSame(MealType::Dinner, MealType::guessFromHour(16));
        $this->assertSame(MealType::Dinner, MealType::guessFromHour(20));
        $this->assertSame(MealType::Snack, MealType::guessFromHour(21));
        $this->assertSame(MealType::Snack, MealType::guessFromHour(3));
    }

    public function test_food_group_menebak_nama_makanan(): void
    {
        $this->assertSame(FoodGroup::Staple, FoodGroup::guess('Nasi goreng'));
        $this->assertSame(FoodGroup::Staple, FoodGroup::guess('Mie ayam'));
        $this->assertSame(FoodGroup::Protein, FoodGroup::guess('Telur ceplok'));
        $this->assertSame(FoodGroup::Protein, FoodGroup::guess('Ayam bakar'));
        $this->assertSame(FoodGroup::Protein, FoodGroup::guess('Tempe orek'));
        $this->assertSame(FoodGroup::Vegetable, FoodGroup::guess('Sayur asem'));
        $this->assertSame(FoodGroup::Vegetable, FoodGroup::guess('Kangkung tumis'));
        $this->assertSame(FoodGroup::Fruit, FoodGroup::guess('Apel'));
        $this->assertSame(FoodGroup::Fruit, FoodGroup::guess('Semangka'));
    }

    public function test_gorengan_dan_jajanan_dianggap_lainnya(): void
    {
        $this->assertSame(FoodGroup::Other, FoodGroup::guess('Pisang goreng'));
        $this->assertSame(FoodGroup::Other, FoodGroup::guess('Kerupuk udang'));
        $this->assertSame(FoodGroup::Other, FoodGroup::guess('Martabak manis'));
        $this->assertSame(FoodGroup::Other, FoodGroup::guess(''));
    }

    public function test_kata_kunci_dicocokkan_per_kata_utuh(): void
    {
        // "teri" tidak boleh cocok di dalam kata lain.
        $this->assertSame(FoodGroup::Other, FoodGroup::guess('Misterius'));
        $this->assertSame(FoodGroup::Protein, FoodGroup::guess('Ikan teri'));
    }

    public function test_kelompok_piring_hanya_empat(): void
    {
        $this->assertCount(4, FoodGroup::plated());
        $this->assertNotContains(FoodGroup::Other, FoodGroup::plated());
    }

    public function test_label_dan_faktor_dari_config(): void
    {
        $this->assertSame('Laki-laki', Gender::Male->label());
        $this->assertSame('Perempuan', Gender::Female->label());
        $this->assertSame('Menurunkan berat', Goal::Lose->label());
        $this->assertSame(-0.15, Goal::Lose->adjustment());
        $this->assertSame(0.0, Goal::Maintain->adjustment());
        $this->assertSame(0.10, Goal::Gain->adjustment());
        $this->assertSame(1.55, ActivityLevel::Moderate->factor());
        $this->assertSame('user', Role::User->value);
        $this->assertSame('admin', Role::Admin->value);
        $this->assertSame('Bukan makanan', ScanStatus::NotFood->label());
        $this->assertSame('Sarapan', MealType::Breakfast->label());
    }
}
