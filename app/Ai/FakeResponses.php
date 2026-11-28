<?php

namespace App\Ai;

/**
 * Jawaban contoh untuk mode tiruan (AI_FAKE=true). Hanya dipakai saat
 * pengembangan dan tes; di produksi `gizisnap.ai.fake` dipaksa false.
 *
 * - Nama berkas berisi "bukan-makanan" -> foto dianggap bukan makanan.
 * - Nama berkas berisi "error"         -> panggilan AI dianggap gagal.
 * - Lainnya                            -> nasi goreng telur dengan 3 item.
 */
class FakeResponses
{
    public static function scan(?string $originalName = null): array
    {
        $name = mb_strtolower((string) $originalName);

        if (str_contains($name, 'error')) {
            throw new \RuntimeException('Kegagalan tiruan untuk pengujian.');
        }

        if (str_contains($name, 'bukan-makanan')) {
            return [
                'is_food' => false,
                'dish_name' => '',
                'confidence' => 0.9,
                'items' => [],
                'notes' => 'Foto tidak berisi makanan atau minuman.',
            ];
        }

        return [
            'is_food' => true,
            'dish_name' => 'Nasi goreng telur',
            'confidence' => 0.86,
            'items' => [
                ['name' => 'Nasi goreng', 'portion_desc' => '1 piring', 'weight_g' => 250, 'calories_kcal' => 418, 'protein_g' => 10, 'carbs_g' => 60, 'fat_g' => 15, 'fiber_g' => 2, 'sugar_g' => 3, 'sodium_mg' => 820],
                ['name' => 'Telur ceplok', 'portion_desc' => '1 butir', 'weight_g' => 60, 'calories_kcal' => 92, 'protein_g' => 6.3, 'carbs_g' => 0.4, 'fat_g' => 7.1, 'fiber_g' => 0, 'sugar_g' => 0.2, 'sodium_mg' => 95],
                ['name' => 'Kerupuk udang', 'portion_desc' => '2 keping', 'weight_g' => 10, 'calories_kcal' => 53, 'protein_g' => 0.5, 'carbs_g' => 6, 'fat_g' => 3, 'fiber_g' => 0, 'sugar_g' => 0.3, 'sodium_mg' => 110],
            ],
            'notes' => 'Mode tiruan: ini jawaban contoh, bukan hasil analisis foto Anda.',
            'health_assessment' => [
                'severity' => 'caution',
                'summary' => 'Mode tiruan: nasi goreng dan kerupuk perlu dibatasi bila punya kondisi gula darah atau tekanan darah.',
                'warnings' => ['Porsi karbohidrat cukup besar dari nasi goreng.', 'Kerupuk menambah natrium.'],
                'avoid' => ['Kerupuk dan gorengan berlebihan', 'Nasi putih porsi besar'],
                'prefer' => ['Tambahan sayur', 'Protein tanpa gula', 'Air putih'],
                'flagged_items' => [
                    ['name' => 'Nasi goreng', 'reason' => 'Karbohidrat tinggi'],
                    ['name' => 'Kerupuk udang', 'reason' => 'Tinggi natrium'],
                ],
            ],
        ];
    }

    public static function chat(string $message, ?int $remainingKcal = null): string
    {
        $remaining = $remainingKcal !== null
            ? "Sisa kalori Anda hari ini sekitar {$remainingKcal} kkal."
            : 'Isi profil dulu agar saya bisa memakai target harian Anda.';

        return "Ini jawaban dari mode tiruan, jadi belum benar-benar membaca pertanyaan Anda. {$remaining}\n\n"
            .'Untuk jawaban asli, isi OPENAI_API_KEY di .env lalu ubah AI_FAKE menjadi false.';
    }
}
