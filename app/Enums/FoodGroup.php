<?php

namespace App\Enums;

/**
 * Kelompok makanan menurut "Isi Piringku" (Kemenkes RI).
 *
 * Dipakai untuk menilai susunan piring pengguna, bukan hanya jumlah kalori.
 * Aplikasi penghitung kalori lain berhenti di angka; GiziSnap menunjukkan
 * apakah isi piringnya sudah seimbang.
 */
enum FoodGroup: string
{
    case Staple = 'staple';
    case Protein = 'protein';
    case Vegetable = 'vegetable';
    case Fruit = 'fruit';
    case Other = 'other';

    public function short(): string
    {
        return match ($this) {
            self::Staple => 'Pokok',
            self::Protein => 'Protein',
            self::Vegetable => 'Sayur',
            self::Fruit => 'Buah',
            self::Other => 'Lainnya',
        };
    }

    /**
     * Kelompok yang ikut dihitung dalam susunan piring. "Lainnya" tidak,
     * karena jajanan dan minuman tidak punya porsi anjuran di piring.
     *
     * @return list<self>
     */
    public static function plated(): array
    {
        return [self::Staple, self::Protein, self::Vegetable, self::Fruit];
    }

    /**
     * Tebak kelompok dari nama makanan.
     *
     * Dipakai untuk hasil scan, yang hanya memberi nama tanpa category.
     * Urutan pemeriksaan penting: "pisang goreng" harus jadi camilan,
     * bukan buah, jadi kata "goreng" diperiksa lebih dulu.
     */
    public static function guess(string $name): self
    {
        $n = mb_strtolower(trim($name));

        if ($n === '') {
            return self::Other;
        }

        // Gorengan dan jajanan manis diperlakukan sebagai camilan, bukan bahan dasarnya.
        if (self::matches($n, '/gorengan|keripik|kerupuk|martabak|donat|cireng|batagor|siomay|pisang goreng|roti isi/u')) {
            return self::Other;
        }

        foreach (self::keywords() as $group => $words) {
            foreach ($words as $word) {
                // Cocokkan per kata utuh supaya "teri" tidak cocok di "misterius".
                if (self::matches($n, '/(?<![\p{L}])'.preg_quote($word, '/').'(?![\p{L}])/u')) {
                    return self::from($group);
                }
            }
        }

        return self::Other;
    }

    private static function matches(string $subject, string $pattern): bool
    {
        return preg_match($pattern, $subject) === 1;
    }

    /**
     * Peta kata kunci per kelompok. Kata yang lebih spesifik diletakkan lebih awal.
     *
     * @return array<string, list<string>>
     */
    private static function keywords(): array
    {
        return [
            'staple' => [
                'nasi', 'mi', 'mie', 'bihun', 'kwetiau', 'lontong', 'ketupat', 'bubur',
                'roti', 'kentang', 'ubi', 'jagung', 'singkong', 'talas', 'sagu', 'oat',
                'pasta', 'spageti',
            ],
            'protein' => [
                'telur', 'ayam', 'ikan', 'daging', 'sapi', 'kambing', 'bebek', 'udang',
                'cumi', 'tempe', 'tahu', 'bakso', 'sosis', 'kacang', 'kedelai', 'rendang',
                'sate', 'lele', 'bandeng', 'tongkol', 'teri', 'sarden', 'susu', 'yogurt',
                'keju', 'perkedel', 'hati',
            ],
            'vegetable' => [
                'sayur', 'bayam', 'kangkung', 'sop', 'asem', 'capcay', 'lalapan', 'timun',
                'mentimun', 'kol', 'kubis', 'wortel', 'brokoli', 'buncis', 'terong', 'labu',
                'jamur', 'selada', 'tauge', 'kacang panjang', 'gado', 'pecel', 'urap',
                'daun', 'sawi', 'pakcoy', 'pare', 'nangka muda',
            ],
            'fruit' => [
                'apel', 'jeruk', 'mangga', 'pepaya', 'pisang', 'semangka', 'melon', 'anggur',
                'alpukat', 'stroberi', 'strawberry', 'nanas', 'jambu', 'kiwi', 'durian',
                'rambutan', 'salak', 'manggis', 'belimbing', 'kelengkeng', 'leci',
            ],
        ];
    }
}
