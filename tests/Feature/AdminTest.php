<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function foodPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tempe goreng',
            'category' => 'Lauk nabati',
            'serving_desc' => '1 potong',
            'serving_weight_g' => 50,
            'calories_kcal' => 118,
            'protein_g' => 9.4,
            'carbs_g' => 7.4,
            'fat_g' => 6.4,
            'fiber_g' => 1.4,
            'sugar_g' => 0.5,
            'sodium_mg' => 120,
            'source' => 'TKPI Kemenkes',
            'is_active' => true,
        ], $overrides);
    }

    public function test_hanya_admin_yang_bisa_membuka_panel(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $this->actingAs($this->onboardedUser())
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($this->adminUser())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Kunjungan beranda');
    }

    public function test_daftar_pengguna_dengan_pencarian(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@example.com']);
        User::factory()->create(['name' => 'Budi', 'email' => 'budi@example.com']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('Budi');

        $this->actingAs($admin)
            ->get('/admin/users?q=siti')
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi');
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_data_admin(): void
    {
        $user = $this->onboardedUser();

        foreach (['/admin/users', '/admin/ai-usage', '/admin/foods', '/admin/foods/create'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_statistik_pemakaian_ai(): void
    {
        $admin = $this->adminUser();
        $user = $this->onboardedUser();

        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'scan',
            'model' => 'fake',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'duration_ms' => 1200,
            'status' => 'success',
            'created_at' => now(),
        ]);
        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'chat',
            'model' => 'fake',
            'status' => 'error',
            'error_code' => 'connection',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/ai-usage')
            ->assertOk()
            ->assertSee('150')
            ->assertSee('connection');

        $this->actingAs($admin)
            ->get('/admin/ai-usage?days=30')
            ->assertOk();
    }

    public function test_makanan_bisa_ditambah_diubah_dan_dihapus(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/admin/foods')->assertOk();
        $this->actingAs($admin)->get('/admin/foods/create')->assertOk();

        $this->actingAs($admin)
            ->post('/admin/foods', $this->foodPayload())
            ->assertRedirect(route('admin.foods.index'))
            ->assertSessionHas('success');

        $food = Food::firstWhere('name', 'Tempe goreng');
        $this->assertNotNull($food);
        $this->assertTrue($food->is_active);

        $this->actingAs($admin)
            ->get('/admin/foods/'.$food->id.'/edit')
            ->assertOk()
            ->assertSee('Tempe goreng');

        $this->actingAs($admin)
            ->put('/admin/foods/'.$food->id, $this->foodPayload([
                'name' => 'Tempe bacem',
                'is_active' => false,
            ]))
            ->assertRedirect(route('admin.foods.index'));

        $food->refresh();
        $this->assertSame('Tempe bacem', $food->name);
        $this->assertFalse($food->is_active);

        $this->actingAs($admin)
            ->delete('/admin/foods/'.$food->id)
            ->assertRedirect(route('admin.foods.index'));

        $this->assertNull(Food::find($food->id));
    }

    public function test_validasi_form_makanan(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post('/admin/foods', [])
            ->assertSessionHasErrors([
                'name', 'category', 'serving_desc', 'serving_weight_g',
                'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'source',
            ]);

        $this->assertDatabaseCount('foods', 0);
    }

    public function test_pencarian_makanan_di_panel_admin(): void
    {
        $admin = $this->adminUser();
        $this->makeFood(['name' => 'Nasi putih']);
        $this->makeFood(['name' => 'Ayam bakar']);

        $this->actingAs($admin)
            ->get('/admin/foods?q=nasi')
            ->assertOk()
            ->assertSee('Nasi putih')
            ->assertDontSee('Ayam bakar');
    }
}
