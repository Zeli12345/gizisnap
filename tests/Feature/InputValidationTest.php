<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Meal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    private function mealPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Menu uji',
            'eaten_date' => today()->toDateString(),
            'eaten_time' => '12:00',
            'items' => [[
                'name' => 'Nasi putih',
                'portion_desc' => '1 porsi',
                'calories_kcal' => 360,
                'protein_g' => 6.8,
                'carbs_g' => 79.2,
                'fat_g' => 0.6,
            ]],
        ], $overrides);
    }

    public function test_jenis_makan_ditebak_dari_jam_makan(): void
    {
        $cases = [
            '09:00' => 'breakfast',
            '12:00' => 'lunch',
            '17:00' => 'dinner',
            '22:00' => 'snack',
        ];

        foreach ($cases as $time => $expected) {
            $user = $this->onboardedUser();

            $this->actingAs($user)
                ->post('/meals', $this->mealPayload(['eaten_time' => $time]))
                ->assertRedirect();

            $this->assertSame(
                $expected,
                Meal::where('user_id', $user->id)->first()->meal_type->value,
                "Jam {$time} seharusnya menjadi {$expected}."
            );
        }
    }

    public function test_tanggal_dan_jam_makan_digabung_menjadi_satu_waktu(): void
    {
        $user = $this->onboardedUser();
        $date = today()->subDay()->toDateString();

        $this->actingAs($user)
            ->post('/meals', $this->mealPayload([
                'eaten_date' => $date,
                'eaten_time' => '08:15',
            ]))
            ->assertRedirect();

        $this->assertSame(
            $date.' 08:15',
            Meal::where('user_id', $user->id)->first()->eaten_at->format('Y-m-d H:i')
        );
    }

    public function test_makanan_baru_tanpa_centang_aktif_disimpan_nonaktif(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post('/admin/foods', [
                'name' => 'Makanan tanpa status',
                'category' => 'Lainnya',
                'serving_desc' => '1 porsi',
                'serving_weight_g' => 100,
                'calories_kcal' => 100,
                'protein_g' => 1,
                'carbs_g' => 10,
                'fat_g' => 1,
                'source' => 'Uji',
            ])
            ->assertRedirect(route('admin.foods.index'));

        $this->assertFalse(Food::firstWhere('name', 'Makanan tanpa status')->is_active);
    }

    public function test_batas_jumlah_air_minum(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)->postJson('/water', ['amount_ml' => 49])->assertStatus(422);
        $this->actingAs($user)->postJson('/water', ['amount_ml' => 2001])->assertStatus(422);
        $this->actingAs($user)->postJson('/water', ['amount_ml' => 50])->assertOk();
        $this->actingAs($user)->postJson('/water', ['amount_ml' => 2000])->assertOk();

        $this->assertSame(2050, (int) $user->waterLogs()->sum('amount_ml'));
    }

    public function test_batas_target_harian(): void
    {
        $user = $this->onboardedUser();

        $valid = [
            'calories_kcal' => 1000,
            'protein_g' => 10,
            'carbs_g' => 20,
            'fat_g' => 10,
            'water_ml' => 500,
        ];

        $this->actingAs($user)->put('/profile/targets', $valid)->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put('/profile/targets', array_merge($valid, ['calories_kcal' => 999]))
            ->assertSessionHasErrors('calories_kcal');

        $this->actingAs($user)
            ->put('/profile/targets', array_merge($valid, ['calories_kcal' => 5001]))
            ->assertSessionHasErrors('calories_kcal');
    }

    public function test_maksimal_dua_puluh_item_makanan(): void
    {
        $user = $this->onboardedUser();

        $items = [];
        for ($i = 1; $i <= 21; $i++) {
            $items[] = [
                'name' => 'Makanan '.$i,
                'calories_kcal' => 100,
                'protein_g' => 1,
                'carbs_g' => 10,
                'fat_g' => 1,
            ];
        }

        $this->actingAs($user)
            ->post('/meals', $this->mealPayload(['items' => $items]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('meals', 0);
    }

    public function test_pesan_chat_tepat_seribu_karakter_diterima(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->postJson('/chat', ['message' => str_repeat('a', 1000)])
            ->assertOk();
    }
}
