<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;

/**
 * Daftar makanan Indonesia untuk input manual.
 *
 * PENTING untuk tim dokumentasi: angka di bawah adalah perkiraan awal per porsi
 * agar aplikasi bisa langsung dicoba. Ganti dengan angka dari Tabel Komposisi
 * Pangan Indonesia (TKPI, Kemenkes) lalu ubah kolom sumber menjadi "TKPI Kemenkes".
 */
class FoodSeeder extends Seeder
{
    public function run(): void
    {
        // nama, kategori, porsi, berat g, kkal, protein, karbo, lemak, serat, gula, natrium mg
        $foods = [
            ['Nasi putih', 'Makanan pokok', '1 centong (100 g)', 100, 175, 3.0, 39.8, 0.3, 0.2, 0.1, 1],
            ['Nasi merah', 'Makanan pokok', '1 centong (100 g)', 100, 149, 2.8, 32.5, 0.4, 1.8, 0.3, 2],
            ['Nasi goreng', 'Makanan pokok', '1 piring (250 g)', 250, 418, 10.0, 60.0, 15.0, 2.0, 3.0, 820],
            ['Nasi uduk', 'Makanan pokok', '1 porsi (150 g)', 150, 256, 4.5, 42.0, 8.0, 0.8, 0.5, 210],
            ['Lontong', 'Makanan pokok', '1 potong (100 g)', 100, 144, 2.6, 31.7, 0.3, 0.3, 0.1, 2],
            ['Mi goreng', 'Makanan pokok', '1 piring (200 g)', 200, 420, 9.0, 56.0, 17.0, 2.5, 3.0, 950],
            ['Mi rebus instan', 'Makanan pokok', '1 bungkus (75 g kering)', 75, 340, 7.0, 47.0, 13.5, 1.5, 2.0, 1100],
            ['Roti tawar', 'Makanan pokok', '2 lembar (50 g)', 50, 133, 4.0, 25.0, 1.8, 1.2, 2.5, 245],
            ['Kentang rebus', 'Makanan pokok', '1 buah sedang (150 g)', 150, 128, 3.0, 29.0, 0.2, 2.6, 1.2, 9],
            ['Ubi rebus', 'Makanan pokok', '1 buah sedang (130 g)', 130, 112, 2.0, 26.0, 0.1, 3.9, 5.4, 36],
            ['Jagung rebus', 'Makanan pokok', '1 tongkol (100 g)', 100, 96, 3.4, 21.0, 1.5, 2.4, 4.5, 15],
            ['Bubur ayam', 'Makanan pokok', '1 mangkuk (300 g)', 300, 372, 15.0, 50.0, 12.0, 1.5, 1.0, 900],

            ['Telur rebus', 'Lauk hewani', '1 butir (55 g)', 55, 77, 6.3, 0.6, 5.3, 0, 0.6, 62],
            ['Telur ceplok', 'Lauk hewani', '1 butir (60 g)', 60, 92, 6.3, 0.4, 7.1, 0, 0.2, 95],
            ['Telur dadar', 'Lauk hewani', '1 butir (65 g)', 65, 120, 7.0, 1.0, 9.8, 0, 0.4, 180],
            ['Ayam goreng (paha)', 'Lauk hewani', '1 potong (80 g)', 80, 211, 18.0, 3.0, 14.0, 0, 0, 260],
            ['Ayam goreng tepung', 'Lauk hewani', '1 potong (110 g)', 110, 320, 20.0, 12.0, 21.0, 0.5, 0.5, 520],
            ['Ayam bakar', 'Lauk hewani', '1 potong (80 g)', 80, 176, 20.0, 4.0, 8.6, 0, 3.0, 340],
            ['Sate ayam', 'Lauk hewani', '5 tusuk dengan bumbu (120 g)', 120, 290, 22.0, 12.0, 17.0, 1.0, 7.0, 520],
            ['Rendang daging', 'Lauk hewani', '1 potong (60 g)', 60, 193, 13.0, 4.0, 14.0, 0.8, 1.0, 290],
            ['Ikan bandeng goreng', 'Lauk hewani', '1 potong (80 g)', 80, 190, 16.0, 0, 14.0, 0, 0, 120],
            ['Ikan tongkol balado', 'Lauk hewani', '1 potong (70 g)', 70, 150, 17.0, 4.0, 7.3, 0.6, 2.0, 310],
            ['Ikan lele goreng', 'Lauk hewani', '1 ekor sedang (90 g)', 90, 230, 16.0, 2.0, 17.5, 0, 0, 140],
            ['Bakso sapi', 'Lauk hewani', '1 mangkuk (6 butir + kuah)', 350, 330, 18.0, 30.0, 15.0, 1.0, 2.0, 1300],
            ['Sosis goreng', 'Lauk hewani', '2 buah (50 g)', 50, 150, 6.0, 4.0, 12.3, 0, 1.0, 520],

            ['Tempe goreng', 'Lauk nabati', '1 potong (25 g)', 25, 59, 3.4, 2.5, 4.0, 0.8, 0.2, 5],
            ['Tahu goreng', 'Lauk nabati', '1 potong (40 g)', 40, 70, 4.4, 1.6, 5.2, 0.4, 0.2, 6],
            ['Tempe bacem', 'Lauk nabati', '1 potong (40 g)', 40, 120, 7.0, 10.0, 5.6, 1.2, 7.5, 160],
            ['Perkedel kentang', 'Lauk nabati', '1 buah (40 g)', 40, 90, 1.8, 10.0, 4.8, 0.8, 0.4, 120],
            ['Tahu isi goreng', 'Lauk nabati', '1 buah (60 g)', 60, 135, 5.0, 10.0, 8.4, 0.8, 0.8, 180],

            ['Sayur bayam bening', 'Sayur', '1 mangkuk (150 g)', 150, 36, 3.0, 5.0, 0.4, 2.0, 1.0, 230],
            ['Tumis kangkung', 'Sayur', '1 porsi (100 g)', 100, 98, 3.0, 5.0, 7.4, 2.1, 1.5, 360],
            ['Sayur asem', 'Sayur', '1 mangkuk (200 g)', 200, 88, 2.8, 15.0, 1.8, 3.5, 6.0, 420],
            ['Sayur sop', 'Sayur', '1 mangkuk (200 g)', 200, 70, 3.0, 10.0, 2.0, 2.6, 3.0, 480],
            ['Capcay', 'Sayur', '1 porsi (150 g)', 150, 130, 6.0, 10.0, 7.4, 2.6, 3.0, 520],
            ['Gado-gado', 'Sayur', '1 porsi (250 g)', 250, 380, 14.0, 30.0, 23.0, 6.0, 9.0, 560],
            ['Lalapan timun dan kol', 'Sayur', '1 porsi (60 g)', 60, 12, 0.6, 2.4, 0.1, 0.8, 1.4, 6],
            ['Sambal', 'Sayur', '1 sendok makan (15 g)', 15, 22, 0.3, 2.0, 1.5, 0.5, 1.2, 190],

            ['Pisang ambon', 'Buah', '1 buah (100 g)', 100, 92, 1.0, 23.0, 0.2, 2.6, 12.0, 1],
            ['Pepaya', 'Buah', '1 potong (100 g)', 100, 46, 0.5, 11.6, 0.1, 1.7, 7.8, 8],
            ['Jeruk manis', 'Buah', '1 buah (100 g)', 100, 45, 0.9, 11.2, 0.2, 1.4, 9.0, 0],
            ['Apel', 'Buah', '1 buah sedang (150 g)', 150, 78, 0.4, 20.7, 0.3, 3.6, 15.0, 2],
            ['Semangka', 'Buah', '1 potong (150 g)', 150, 42, 0.8, 10.5, 0.2, 0.6, 9.0, 2],
            ['Mangga harum manis', 'Buah', '1/2 buah (150 g)', 150, 99, 1.2, 24.0, 0.6, 2.6, 21.0, 2],

            ['Teh manis', 'Minuman', '1 gelas (250 ml)', 250, 90, 0, 22.5, 0, 0, 22.5, 5],
            ['Es jeruk', 'Minuman', '1 gelas (250 ml)', 250, 110, 0.5, 27.0, 0.1, 0.3, 25.0, 3],
            ['Kopi susu gula aren', 'Minuman', '1 gelas (250 ml)', 250, 190, 5.0, 28.0, 6.5, 0, 26.0, 90],
            ['Susu cokelat kemasan', 'Minuman', '1 kotak (200 ml)', 200, 140, 5.0, 20.0, 4.6, 0.8, 18.0, 120],
            ['Susu UHT plain', 'Minuman', '1 kotak (200 ml)', 200, 120, 6.0, 9.0, 6.6, 0, 9.0, 100],
            ['Jus alpukat', 'Minuman', '1 gelas (250 ml)', 250, 260, 3.0, 30.0, 14.5, 5.0, 24.0, 40],
            ['Air putih', 'Minuman', '1 gelas (250 ml)', 250, 0, 0, 0, 0, 0, 0, 0],

            ['Gorengan bakwan', 'Jajanan', '1 buah (40 g)', 40, 113, 2.0, 12.0, 6.4, 0.8, 0.5, 150],
            ['Pisang goreng', 'Jajanan', '1 buah (60 g)', 60, 160, 1.5, 22.0, 7.5, 1.4, 10.0, 40],
            ['Cireng', 'Jajanan', '3 buah (60 g)', 60, 180, 0.6, 30.0, 6.4, 0.4, 0.2, 260],
            ['Siomay', 'Jajanan', '1 porsi (5 buah + bumbu)', 200, 330, 15.0, 38.0, 13.0, 2.0, 8.0, 720],
            ['Batagor', 'Jajanan', '1 porsi (150 g)', 150, 340, 12.0, 32.0, 18.5, 1.5, 6.0, 640],
            ['Martabak manis', 'Jajanan', '1 potong (75 g)', 75, 260, 5.0, 36.0, 10.6, 0.8, 18.0, 200],
            ['Roti isi cokelat', 'Jajanan', '1 buah (60 g)', 60, 200, 4.5, 30.0, 6.9, 1.2, 11.0, 180],
            ['Kerupuk udang', 'Jajanan', '2 keping (10 g)', 10, 53, 0.5, 6.0, 3.0, 0, 0.3, 110],
            ['Keripik singkong', 'Jajanan', '1 bungkus kecil (30 g)', 30, 160, 0.6, 19.0, 9.0, 1.2, 0.5, 180],
            ['Donat gula', 'Jajanan', '1 buah (50 g)', 50, 200, 3.0, 24.0, 10.2, 0.6, 10.0, 170],
        ];

        foreach ($foods as [$name, $category, $serving, $weight, $kcal, $p, $c, $f, $fiber, $sugar, $sodium]) {
            Food::updateOrCreate(['name' => $name], [
                'category' => $category,
                'serving_desc' => $serving,
                'serving_weight_g' => $weight,
                'calories_kcal' => $kcal,
                'protein_g' => $p,
                'carbs_g' => $c,
                'fat_g' => $f,
                'fiber_g' => $fiber,
                'sugar_g' => $sugar,
                'sodium_mg' => $sodium,
                'source' => 'Perkiraan awal, perlu dicek dengan TKPI',
                'is_active' => true,
            ]);
        }
    }
}
