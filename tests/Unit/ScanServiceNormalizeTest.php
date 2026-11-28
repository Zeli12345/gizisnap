<?php

namespace Tests\Unit;

use App\Exceptions\AiFailure;
use App\Services\ScanService;
use Tests\TestCase;

class ScanServiceNormalizeTest extends TestCase
{
    private function normalize(array $raw): array
    {
        return app(ScanService::class)->normalize($raw);
    }

    public function test_jawaban_valid_dirapikan_dan_dibatasi(): void
    {
        $result = $this->normalize([
            'is_food' => true,
            'dish_name' => '  Nasi goreng  ',
            'confidence' => 1.5,
            'notes' => str_repeat('a', 600),
            'items' => [[
                'name' => '  Nasi goreng ',
                'portion_desc' => '',
                'weight_g' => -5,
                'calories_kcal' => 9999,
                'protein_g' => 10,
                'carbs_g' => 60,
                'fat_g' => 15,
                'fiber_g' => 2,
                'sugar_g' => 3,
                'sodium_mg' => 999999,
            ]],
            'health_assessment' => [
                'severity' => 'tinggi',
                'summary' => '  Perlu dibatasi  ',
                'warnings' => ['  Kurangi gorengan  ', '', 5],
                'avoid' => 'bukan array',
                'prefer' => ['Sayur'],
                'flagged_items' => [
                    ['name' => 'Nasi goreng', 'reason' => 'Karbohidrat tinggi'],
                    ['reason' => 'tanpa nama'],
                ],
            ],
        ]);

        $this->assertTrue($result['is_food']);
        $this->assertSame('Nasi goreng', $result['dish_name']);
        $this->assertSame(1.0, $result['confidence']);
        $this->assertSame(503, mb_strlen($result['notes']));
        $this->assertCount(1, $result['items']);

        $item = $result['items'][0];
        $this->assertSame('Nasi goreng', $item['name']);
        $this->assertSame('1 porsi', $item['portion_desc']);
        $this->assertSame(0.0, $item['weight_g']);
        $this->assertSame(5000.0, $item['calories_kcal']);
        $this->assertSame(10.0, $item['protein_g']);
        $this->assertSame(99999.0, $item['sodium_mg']);
        $this->assertTrue($item['needs_check']);

        $this->assertSame('safe', $result['health_assessment']['severity']);
        $this->assertSame('Perlu dibatasi', $result['health_assessment']['summary']);
        $this->assertSame(['Kurangi gorengan'], $result['health_assessment']['warnings']);
        $this->assertSame([], $result['health_assessment']['avoid']);
        $this->assertSame(['Sayur'], $result['health_assessment']['prefer']);
        $this->assertCount(1, $result['health_assessment']['flagged_items']);
    }

    public function test_item_tanpa_nama_dibuang_dan_maksimal_lima_belas(): void
    {
        $items = [['name' => '   ', 'calories_kcal' => 10]];

        for ($i = 1; $i <= 20; $i++) {
            $items[] = ['name' => 'Makanan '.$i, 'calories_kcal' => 100];
        }

        $result = $this->normalize([
            'is_food' => true,
            'confidence' => 0.5,
            'items' => $items,
        ]);

        $this->assertCount(15, $result['items']);
        $this->assertSame('Makanan 1', $result['items'][0]['name']);
        $this->assertSame('Makanan 15', $result['items'][14]['name']);
    }

    public function test_nama_hidangan_jatuh_ke_item_pertama(): void
    {
        $result = $this->normalize([
            'is_food' => true,
            'dish_name' => '',
            'confidence' => 0.4,
            'items' => [['name' => 'Bakso', 'calories_kcal' => 300]],
        ]);

        $this->assertSame('Bakso', $result['dish_name']);
        $this->assertTrue($result['items'][0]['needs_check']);
    }

    public function test_jawaban_dibungkus_satu_kunci_dibuka(): void
    {
        $result = $this->normalize([
            'schema_definition' => [
                'is_food' => false,
                'dish_name' => '',
                'confidence' => 0.9,
                'items' => [],
                'notes' => 'Bukan makanan',
            ],
        ]);

        $this->assertFalse($result['is_food']);
        $this->assertSame([], $result['items']);
    }

    public function test_skema_tidak_lengkap_melempar_ai_failure(): void
    {
        $this->expectException(AiFailure::class);
        $this->expectExceptionMessage('Jawaban AI tidak sesuai format. Coba lagi.');

        $this->normalize(['items' => []]);
    }

    public function test_items_bukan_array_melempar_ai_failure(): void
    {
        $this->expectException(AiFailure::class);

        $this->normalize(['is_food' => true, 'items' => 'nasi']);
    }
}
