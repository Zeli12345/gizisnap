<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_jawaban_ai_tiruan_dipaksa_mati_di_produksi(): void
    {
        config(['gizisnap.ai.fake' => true]);
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->boot();

        $this->assertFalse(config('gizisnap.ai.fake'));
    }

    public function test_jawaban_ai_tiruan_tetap_aktif_di_luar_produksi(): void
    {
        config(['gizisnap.ai.fake' => true]);
        $this->app['env'] = 'testing';

        (new AppServiceProvider($this->app))->boot();

        $this->assertTrue(config('gizisnap.ai.fake'));
    }
}
