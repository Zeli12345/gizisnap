<?php

namespace Tests\Unit;

use App\Ai\FakeResponses;
use RuntimeException;
use Tests\TestCase;

class FakeResponsesTest extends TestCase
{
    public function test_scan_bawaan_berisi_tiga_item_dan_penilaian_hati_hati(): void
    {
        $result = FakeResponses::scan('nasi.jpg');

        $this->assertTrue($result['is_food']);
        $this->assertSame('Nasi goreng telur', $result['dish_name']);
        $this->assertSame(0.86, $result['confidence']);
        $this->assertCount(3, $result['items']);
        $this->assertSame('Nasi goreng', $result['items'][0]['name']);
        $this->assertSame('caution', $result['health_assessment']['severity']);
        $this->assertStringContainsString('Mode tiruan', $result['notes']);
    }

    public function test_nama_berkas_menentukan_jawaban_tiruan(): void
    {
        $notFood = FakeResponses::scan('foto-BUKAN-makanan.jpg');

        $this->assertFalse($notFood['is_food']);
        $this->assertSame([], $notFood['items']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kegagalan tiruan untuk pengujian.');

        FakeResponses::scan('foto-error.jpg');
    }

    public function test_laporan_mengikuti_periode_dan_statistik(): void
    {
        $weekly = FakeResponses::report(7, ['logged_days' => 4, 'avg_calories' => 1800]);
        $monthly = FakeResponses::report(30, ['logged_days' => 20, 'avg_calories' => 1900]);

        $this->assertStringContainsString('seminggu', $weekly['headline']);
        $this->assertStringContainsString('4 hari', $weekly['summary']);
        $this->assertStringContainsString('1800 kkal', $weekly['summary']);
        $this->assertStringContainsString('sebulan', $monthly['headline']);
        $this->assertStringContainsString('20 hari', $monthly['summary']);
        $this->assertNotEmpty($weekly['changes']);
        $this->assertNotEmpty($weekly['reduce']);
        $this->assertNotEmpty($weekly['increase']);
    }

    public function test_chat_menyebut_sisa_kalori_bila_tersedia(): void
    {
        $withRemaining = FakeResponses::chat('Apa menu sehat?', 450);
        $withoutRemaining = FakeResponses::chat('Apa menu sehat?');

        $this->assertStringContainsString('450 kkal', $withRemaining);
        $this->assertStringContainsString('mode tiruan', $withRemaining);
        $this->assertStringContainsString('Isi profil dulu', $withoutRemaining);
    }
}
