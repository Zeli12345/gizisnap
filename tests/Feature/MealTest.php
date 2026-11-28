<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Nasi putih dan ayam',
            'eaten_date' => today()->toDateString(),
            'eaten_time' => '12:30',
            'notes' => 'Makan siang di sekolah',
            'items' => [[
                'name' => 'Nasi putih',
                'portion_desc' => '1 porsi',
                'weight_g' => 200,
                'calories_kcal' => 360,
                'protein_g' => 6.8,
                'carbs_g' => 79.2,
                'fat_g' => 0.6,
                'fiber_g' => 0.8,
                'sugar_g' => 0.2,
                'sodium_mg' => 2,
            ], [
                'name' => 'Ayam bakar',
                'portion_desc' => '1 potong',
                'weight_g' => 100,
                'calories_kcal' => 226,
                'protein_g' => 27,
                'carbs_g' => 0,
                'fat_g' => 13,
            ]],
        ], $overrides);
    }

    public function test_daftar_makanan_tampil(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user, ['title' => 'Nasi putih']);

        $this->actingAs($user)
            ->get('/meals')
            ->assertOk()
            ->assertSee('Nasi putih');
    }

    public function test_form_tambah_bisa_dibuka_dengan_makanan_pilihan(): void
    {
        $user = $this->onboardedUser();
        $food = $this->makeFood(['name' => 'Bubur ayam']);

        $this->actingAs($user)->get('/meals/create')->assertOk();

        $this->actingAs($user)
            ->get('/meals/create?food='.$food->id)
            ->assertOk()
            ->assertSee('Bubur ayam');
    }

    public function test_catatan_makan_baru_menghitung_total_di_server(): void
    {
        $user = $this->onboardedUser();
        $food = $this->makeFood();
        $payload = $this->payload();
        $payload['items'][0]['food_id'] = $food->id;

        $this->actingAs($user)
            ->post('/meals', $payload)
            ->assertRedirect(route('dashboard', ['date' => today()->toDateString()]))
            ->assertSessionHas('success');

        $meal = Meal::firstWhere('user_id', $user->id);
        $this->assertNotNull($meal);
        $this->assertSame('manual', $meal->source);
        $this->assertSame('lunch', $meal->meal_type->value);
        $this->assertSame(586.0, $meal->total_calories_kcal);
        $this->assertSame(33.8, $meal->total_protein_g);
        $this->assertCount(2, $meal->items);
        $this->assertSame($food->id, $meal->items->first()->food_id);
    }

    public function test_jenis_makan_bisa_dipilih_manual(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/meals', $this->payload(['meal_type' => 'snack']))
            ->assertRedirect();

        $this->assertSame('snack', Meal::firstWhere('user_id', $user->id)->meal_type->value);
    }

    public function test_validasi_catatan_makan(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/meals', $this->payload(['items' => [], 'eaten_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['items', 'eaten_date']);

        $this->actingAs($user)
            ->post('/meals', $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('meals', 0);
    }

    public function test_catatan_makan_bisa_diubah_dan_koreksi_item_ditandai(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);

        $payload = $this->payload(['title' => 'Nasi dan telur']);
        $payload['items'] = [[
            'name' => 'Nasi putih',
            'portion_desc' => '1 porsi',
            'weight_g' => 200,
            'calories_kcal' => 400,
            'protein_g' => 7,
            'carbs_g' => 80,
            'fat_g' => 1,
        ]];

        $this->actingAs($user)
            ->put('/meals/'.$meal->id, $payload)
            ->assertRedirect(route('dashboard', ['date' => today()->toDateString()]));

        $meal->refresh();
        $this->assertSame('Nasi dan telur', $meal->title);
        $this->assertSame(400.0, $meal->total_calories_kcal);
        $this->assertTrue($meal->items->first()->is_edited);
    }

    public function test_item_yang_tidak_diubah_tidak_ditandai_koreksi(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);

        $this->actingAs($user)
            ->put('/meals/'.$meal->id, $this->payload(['title' => 'Judul baru']))
            ->assertRedirect();

        $this->assertFalse($meal->fresh()->items->firstWhere('name', 'Nasi putih')->is_edited);
    }

    public function test_hanya_pemilik_yang_bisa_mengubah_melihat_dan_menghapus(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $meal = $this->makeMeal($owner);

        $this->actingAs($other)->get('/meals/'.$meal->id.'/edit')->assertForbidden();
        $this->actingAs($other)->put('/meals/'.$meal->id, $this->payload())->assertForbidden();
        $this->actingAs($other)->delete('/meals/'.$meal->id)->assertForbidden();

        $this->assertNotSoftDeleted($meal);
    }

    public function test_pemilik_bisa_membuka_form_ubah_dan_menghapus(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user);

        $this->actingAs($user)->get('/meals/'.$meal->id.'/edit')->assertOk();

        $this->actingAs($user)
            ->delete('/meals/'.$meal->id)
            ->assertRedirect(route('dashboard', ['date' => today()->toDateString()]));

        $this->assertSoftDeleted($meal);
    }

    public function test_makanan_bisa_dicatat_ulang_untuk_hari_ini(): void
    {
        $user = $this->onboardedUser();
        $meal = $this->makeMeal($user, ['eaten_at' => now()->subDay(), 'title' => 'Nasi kemarin']);

        $this->actingAs($user)
            ->post('/meals/'.$meal->id.'/repeat')
            ->assertRedirect(route('dashboard'));

        $this->assertSame(2, $user->meals()->count());

        $copy = $user->meals()->where('id', '!=', $meal->id)->first();
        $this->assertSame('Nasi kemarin', $copy->title);
        $this->assertTrue($copy->eaten_at->isToday());
        $this->assertSame($meal->total_calories_kcal, $copy->total_calories_kcal);
    }

    public function test_pencarian_makanan_hanya_mengembalikan_yang_aktif(): void
    {
        $user = $this->onboardedUser();
        $this->makeFood(['name' => 'Nasi putih']);
        $this->makeFood(['name' => 'Nasi merah']);
        $this->makeFood(['name' => 'Nasi tidak aktif', 'is_active' => false]);
        $this->makeFood(['name' => 'Ayam bakar']);

        $response = $this->actingAs($user)
            ->getJson('/foods/search?q=nasi')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Nasi putih', $names);
        $this->assertContains('Nasi merah', $names);
        $this->assertNotContains('Nasi tidak aktif', $names);
    }

    public function test_pencarian_tanpa_kata_kunci_mengembalikan_maksimal_dua_belas(): void
    {
        $user = $this->onboardedUser();

        for ($i = 1; $i <= 15; $i++) {
            $this->makeFood(['name' => 'Makanan '.$i]);
        }

        $this->actingAs($user)
            ->getJson('/foods/search')
            ->assertOk()
            ->assertJsonCount(12, 'data');
    }
}
