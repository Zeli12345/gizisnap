<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\NutritionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_laporan_tampil_dengan_periode_valid(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)->get('/reports')->assertOk()->assertViewHas('period', 7);

        $this->actingAs($user)
            ->get('/reports?period=30')
            ->assertOk()
            ->assertViewHas('period', 30);

        $this->actingAs($user)
            ->get('/reports?period=99')
            ->assertOk()
            ->assertViewHas('period', 7);
    }

    public function test_admin_dialihkan_dari_halaman_laporan(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/reports')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)
            ->postJson('/reports/ai')
            ->assertForbidden();
    }

    public function test_laporan_tanpa_catatan_makan_ditolak(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->postJson('/reports/ai')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Belum ada catatan makan pada periode ini. Catat beberapa hari dulu, lalu buka laporan lagi.');
    }

    public function test_laporan_ai_dibuat_dan_disimpan(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);

        $this->actingAs($user)
            ->postJson('/reports/ai')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $report = NutritionReport::firstWhere('user_id', $user->id);
        $this->assertNotNull($report);
        $this->assertSame('ready', $report->status);
        $this->assertTrue($report->isReady());
        $this->assertSame('fake', $report->model);
        $this->assertStringContainsString('seminggu', $report->content['headline']);
        $this->assertNotEmpty($report->content['changes']);
    }

    public function test_laporan_tidak_dibuat_ulang_bila_data_sama(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);

        $this->actingAs($user)->postJson('/reports/ai')->assertOk();
        $this->actingAs($user)->postJson('/reports/ai')->assertOk();

        $this->assertSame(1, NutritionReport::where('user_id', $user->id)->count());
    }

    public function test_laporan_lewat_formulir_biasa_memberi_pesan_sukses(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);

        $this->actingAs($user)
            ->post('/reports/ai', ['period' => 30])
            ->assertSessionHas('success', 'Laporan AI sudah diperbarui.');

        $this->assertSame(30, NutritionReport::firstWhere('user_id', $user->id)->period_days);
    }

    public function test_kuota_laporan_habis_mengembalikan_429(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);
        config(['gizisnap.ai.daily_report_limit' => 1]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => 'insight',
            'model' => 'fake',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/reports/ai')
            ->assertStatus(429);
    }

    public function test_halaman_laporan_menandai_belum_ada_analisis(): void
    {
        $user = $this->onboardedUser();
        $this->makeMeal($user);

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk()
            ->assertViewHas('needs_report', true);
    }
}
