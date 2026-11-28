<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use App\Services\ScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    private function scanPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Nasi goreng telur',
            'eaten_date' => today()->toDateString(),
            'eaten_time' => '12:00',
            'notes' => 'Hasil scan',
            'items' => [[
                'name' => 'Nasi goreng',
                'portion_desc' => '1 piring',
                'weight_g' => 250,
                'calories_kcal' => 418,
                'protein_g' => 10,
                'carbs_g' => 60,
                'fat_g' => 15,
                'fiber_g' => 2,
                'sugar_g' => 3,
                'sodium_mg' => 820,
            ]],
        ], $overrides);
    }

    public function test_halaman_scan_tampil(): void
    {
        $user = $this->onboardedUser();
        $this->makeScan($user, ['dish_name' => 'Nasi goreng']);

        $this->actingAs($user)
            ->get('/scan')
            ->assertOk();
    }

    public function test_foto_terunggah_dan_dianalisis(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);

        $this->mock(ScanService::class, function (MockInterface $mock) use ($scan) {
            $mock->shouldReceive('createAndAnalyze')->once()->andReturn($scan);
        });

        $this->actingAs($user)
            ->post('/scan', ['photo' => $this->fakeJpeg()])
            ->assertRedirect(route('scan.show', $scan));
    }

    public function test_validasi_foto_scan(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)
            ->post('/scan', [])
            ->assertSessionHasErrors('photo');

        $this->actingAs($user)
            ->post('/scan', ['photo' => \Illuminate\Http\UploadedFile::fake()->create('catatan.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');
    }

    public function test_hasil_scan_tampil_untuk_pemilik_dan_orang_lain_ditolak(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $scan = $this->makeScan($owner);

        $this->actingAs($owner)->get('/scan/'.$scan->id)->assertOk();
        $this->actingAs($other)->get('/scan/'.$scan->id)->assertForbidden();
    }

    public function test_foto_scan_hanya_bisa_dibuka_pemiliknya(): void
    {
        Storage::fake('local');

        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $path = 'scans/'.$owner->id.'/foto.jpg';
        Storage::disk('local')->put($path, 'isi-foto');
        $scan = $this->makeScan($owner, ['image_path' => $path]);

        $this->actingAs($owner)
            ->get('/media/scans/'.$scan->id)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=86400, private');

        $this->actingAs($other)
            ->get('/media/scans/'.$scan->id)
            ->assertForbidden();

        Storage::disk('local')->delete($path);

        $this->actingAs($owner)
            ->get('/media/scans/'.$scan->id)
            ->assertNotFound();
    }

    public function test_scan_gagal_bisa_dianalisis_ulang(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user, [
            'status' => 'failed',
            'ai_result' => null,
            'dish_name' => null,
            'error_message' => 'Analisis gagal. Coba lagi.',
        ]);

        $this->actingAs($user)
            ->post('/scan/'.$scan->id.'/retry')
            ->assertRedirect(route('scan.show', $scan));

        $this->assertSame('completed', $scan->fresh()->status->value);
    }

    public function test_scan_yang_tidak_gagal_tidak_bisa_diulang(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);

        $this->actingAs($user)
            ->post('/scan/'.$scan->id.'/retry')
            ->assertStatus(409);
    }

    public function test_hasil_scan_bisa_disimpan_sebagai_catatan_makan(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);

        $this->actingAs($user)
            ->post('/scan/'.$scan->id.'/meal', $this->scanPayload())
            ->assertRedirect(route('dashboard', ['date' => today()->toDateString()]))
            ->assertSessionHas('success');

        $meal = $scan->fresh()->meal;
        $this->assertNotNull($meal);
        $this->assertSame('scan', $meal->source);
        $this->assertSame(418.0, $meal->total_calories_kcal);
    }

    public function test_hasil_scan_belum_selesai_tidak_bisa_disimpan(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user, ['status' => 'not_food', 'ai_result' => null]);

        $this->actingAs($user)
            ->post('/scan/'.$scan->id.'/meal', $this->scanPayload())
            ->assertSessionHas('error');

        $this->assertNull($scan->fresh()->meal);
    }

    public function test_hasil_scan_tidak_bisa_disimpan_dua_kali(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user);
        $this->makeMeal($user, ['scan_id' => $scan->id]);

        $this->actingAs($user)
            ->post('/scan/'.$scan->id.'/meal', $this->scanPayload())
            ->assertSessionHas('error');

        $this->assertSame(1, $scan->fresh()->meal()->count());
    }

    public function test_menyimpan_hasil_scan_milik_orang_lain_ditolak(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $scan = $this->makeScan($owner);

        $this->actingAs($other)
            ->post('/scan/'.$scan->id.'/meal', $this->scanPayload())
            ->assertForbidden();
    }

    public function test_galat_ai_ditampilkan_sebagai_pesan_error(): void
    {
        $user = $this->onboardedUser();
        $scan = $this->makeScan($user, ['status' => 'pending', 'ai_result' => null]);

        $this->mock(ScanService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createAndAnalyze')
                ->once()
                ->andThrow(new \App\Exceptions\AiFailure('Layanan AI sedang ramai. Coba lagi sebentar lagi.', 'rate_limited'));
        });

        $this->actingAs($user)
            ->post('/scan', ['photo' => $this->fakeJpeg()])
            ->assertSessionHas('error', 'Layanan AI sedang ramai. Coba lagi sebentar lagi.');
    }
}
