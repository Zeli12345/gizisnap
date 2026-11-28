<?php

namespace Tests\Feature;

use App\Models\WeightLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_berat_badan_tampil(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->get('/weight')
            ->assertOk()
            ->assertSee('65');
    }

    public function test_berat_baru_dicatat_dan_profil_mengikuti_catatan_terbaru(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/weight', [
                'weight_kg' => 68.5,
                'logged_on' => today()->toDateString(),
                'note' => 'Setelah olahraga',
            ])
            ->assertSessionHas('success');

        $this->assertSame(68.5, $user->weightLogs()->whereDate('logged_on', today())->first()->weight_kg);
        $this->assertSame(68.5, $user->fresh()->healthProfile->weight_kg);
        $this->assertSame(1, $user->weightLogs()->count());
    }

    public function test_berat_tanggal_lampau_tidak_menimpa_berat_terbaru(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/weight', [
                'weight_kg' => 60,
                'logged_on' => today()->subWeek()->toDateString(),
            ])
            ->assertSessionHas('success');

        $this->assertSame(2, $user->weightLogs()->count());
        $this->assertSame(65.0, $user->fresh()->healthProfile->weight_kg);
    }

    public function test_validasi_berat_dan_tanggal(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/weight', [
                'weight_kg' => 10,
                'logged_on' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['weight_kg', 'logged_on']);
    }

    public function test_pemilik_bisa_menghapus_catatan_berat(): void
    {
        $user = $this->onboardedUser();
        $old = $user->weightLogs()->create([
            'weight_kg' => 64,
            'logged_on' => today()->subDay()->toDateString(),
        ]);

        $this->actingAs($user)
            ->delete('/weight/'.$old->id)
            ->assertSessionHas('success');

        $this->assertNull(WeightLog::find($old->id));
    }

    public function test_catatan_terakhir_tidak_boleh_dihapus(): void
    {
        $user = $this->onboardedUser();
        $only = $user->weightLogs()->first();

        $this->actingAs($user)
            ->delete('/weight/'.$only->id)
            ->assertSessionHas('error');

        $this->assertNotNull(WeightLog::find($only->id));
    }

    public function test_catatan_milik_orang_lain_tidak_boleh_dihapus(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $log = $owner->weightLogs()->first();

        $this->actingAs($other)
            ->delete('/weight/'.$log->id)
            ->assertForbidden();

        $this->assertNotNull(WeightLog::find($log->id));
    }
}
