<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Str;

/**
 * Menyalin makanan hasil scan pengguna ke daftar makanan admin.
 * Makanan baru langsung aktif secara default, tetapi tetap ditandai
 * sumbernya supaya admin bisa memeriksa ulang.
 */
class FoodCatalog
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return int Jumlah makanan baru yang ditambahkan.
     */
    public function addFromScan(array $items): int
    {
        $added = 0;

        foreach ($items as $item) {
            $name = Str::limit(trim((string) ($item['name'] ?? '')), 120, '');

            if ($name === '') {
                continue;
            }

            if ($this->exists($name)) {
                continue;
            }

            $number = fn (string $key) => round(max(0, (float) ($item[$key] ?? 0)), 1);

            Food::create([
                'name' => $name,
                'category' => 'Hasil scan',
                'serving_desc' => Str::limit(trim((string) ($item['portion_desc'] ?? '1 porsi')) ?: '1 porsi', 80, ''),
                'serving_weight_g' => max(1, $number('weight_g')),
                'calories_kcal' => $number('calories_kcal'),
                'protein_g' => $number('protein_g'),
                'carbs_g' => $number('carbs_g'),
                'fat_g' => $number('fat_g'),
                'fiber_g' => $number('fiber_g'),
                'sugar_g' => $number('sugar_g'),
                'sodium_mg' => $number('sodium_mg'),
                'source' => 'Scan pengguna',
                'is_active' => true,
            ]);

            $added++;
        }

        return $added;
    }

    private function exists(string $name): bool
    {
        return Food::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
    }
}
