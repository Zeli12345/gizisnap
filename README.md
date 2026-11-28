# GiziSnap

**Foto makanan, tahu gizinya.**

GiziSnap adalah aplikasi penghitung gizi berbasis web yang dibuat oleh tim SMK PPLG. Cukup foto makanannya, AI menganalisis dan memperkirakan porsi, kalori, serta zat gizi tiap item. Hasil scan bisa dicatat sebagai log harian, dibahas dengan chatbot gizi, dan dirangkum dalam laporan mingguan/bulanan.

Aplikasi ini dirancang khusus untuk konteks Indonesia: AI mengenal masakan dan porsi rumah tangga Indonesia (centong, piring, mangkuk, potong, sendok makan, gelas), dan penilaian piring mengikuti pedoman **"Isi Piringku"** dari Kemenkes RI.

## Fitur

- **Scan foto makanan** — kenali makanan/minuman di foto, perkirakan porsi (ukuran rumah tangga Indonesia) dan gizinya: kalori, protein, karbo, lemak, serat, gula, natrium. Satu foto bisa berisi beberapa item (maksimal 15).
- **Penilaian kesehatan** — hasil scan dinilai terhadap kondisi kesehatan pengguna (misalnya diabetes, hipertensi) beserta saran hindari/utamakan dan item yang ditandai.
- **Isi Piringku** — makanan dikelompokkan menurut pedoman Kemenkes RI (pokok, protein, sayur, buah) untuk menilai keseimbangan piring, bukan hanya total kalori.
- **Target harian** — kebutuhan kalori dihitung dari profil: usia, gender, berat/tinggi badan, tingkat aktivitas, dan tujuan (menurunkan, menjaga, atau menaikkan berat badan).
- **Chatbot gizi** — tanya jawab dalam bahasa Indonesia dengan konteks data pengguna, misalnya sisa kalori hari ini. Percakapan bisa lebih dari satu dan disimpan per pengguna.
- **Laporan periodik** — ringkasan pola makan mingguan/bulanan hasil analisis AI: perubahan yang terlihat, kebiasaan yang perlu dikurangi dan ditambah, plus penyemangat.
- **Login Google** — lewat Laravel Socialite; akun dengan email tertentu otomatis menjadi admin.
- **Mode tiruan (AI_FAKE)** — jawaban AI berupa contoh tanpa memanggil API, gratis untuk pengembangan dan tes.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite |
| Database | MySQL (SQLite in-memory untuk testing) |
| AI | Laravel AI — OpenAI atau CommandCode (DeepSeek, GPT, Claude, Gemini, Qwen, GLM, Kimi) |
| Login | Google OAuth (Laravel Socialite) |
| Gambar | Intervention Image |
| Testing | Pest / PHPUnit |

## Cara Kerja Singkat

1. Pengguna memfoto makanan dan memilih jenis makan (sarapan, makan siang, makan malam, camilan — bisa ditebak otomatis dari jam).
2. Foto dikirim ke agent **FoodScanner** yang mengubahnya menjadi data gizi terstruktur (JSON, tervalidasi skema).
3. Hasil scan ditampilkan: daftar item, gizi per item, penilaian kesehatan, dan kelompok Isi Piringku.
4. Pengguna menyimpan scan sebagai log harian; total gizi dibandingkan dengan target harian dari profil.
5. Pengguna bisa bertanya ke **NutritionCoach** atau meminta **ReportAnalyst** merangkum pola makannya per minggu/bulan.

## Persiapan

- PHP 8.3+ dan Composer
- Node.js dan npm
- MySQL berjalan (Laragon/XAMPP), sudah buat database `gizisnap`

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Atau sekali jalan (install, migrate, build aset):

```bash
composer setup
```

Isi `.env` minimal:

- `DB_DATABASE=gizisnap` (sudah diisi default)
- `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` untuk login Google
- `ADMIN_EMAIL` — akun dengan email ini otomatis menjadi admin
- `OPENAI_API_KEY` atau `COMMANDCODE_API_KEY`, sesuai nilai `AI_PROVIDER` (`openai` atau `commandcode`)

Untuk pengembangan, biarkan `AI_FAKE=true` agar jawaban AI berupa contoh tanpa biaya API. Di produksi (`APP_ENV=production`) nilai ini dipaksa `false`, jadi jawaban tiruan tidak pernah sampai ke pengguna.

Pengaturan AI lainnya di `.env`: model scan/chat (`AI_SCAN_MODEL`, `AI_CHAT_MODEL`), ketelitian reasoning (`AI_REASONING_EFFORT`), dan limit pemakaian per pengguna (harian dan per menit).

## Menjalankan

```bash
composer dev        # server Laravel + Vite sekaligus
# atau terpisah:
php artisan serve
npm run dev
```

## Testing

```bash
composer test
```

Tes memakai SQLite in-memory dan `AI_FAKE=true`, jadi tidak butuh koneksi MySQL maupun API AI. Mode tiruan juga bisa dipicu lewat nama berkas foto: berisi `error` untuk menguji kegagalan, `bukan-makanan` untuk foto non-makanan.

## Struktur Penting

```
app/Ai/Agents/          Agent AI: FoodScanner, NutritionCoach, ReportAnalyst
app/Enums/              ActivityLevel, FoodGroup, MealType, ScanStatus, dll.
app/Services/           Logika utama (ChatService, dll.)
app/Ai/FakeResponses.php  Jawaban contoh untuk mode AI_FAKE
config/gizisnap.php     Konfigurasi khusus GiziSnap (target gizi, limit AI)
docs/rancangan-prompt.md  Rancangan lengkap prompt FoodScanner
```

## Keamanan

- Jangan pernah commit `.env` — semua kunci API bersifat rahasia; nilai rahasia dibagikan ketua lewat chat pribadi.
- Limit pemakaian AI per pengguna (scan/chat harian dan per menit) diatur lewat `AI_DAILY_SCAN_LIMIT`, `AI_DAILY_CHAT_LIMIT`, dan `AI_PER_MINUTE_LIMIT`, dengan pesan kuota yang ramah saat habis.
- Prompt dilindungi dari injeksi: teks di dalam foto dan isi pesan pengguna diperlakukan sebagai data, bukan perintah.
- Aplikasi tidak mendiagnosis penyakit dan tidak menyarankan diet ekstrem; saran medis selalu diarahkan ke dokter atau ahli gizi.

## Kontribusi

Anggota tim bekerja di branch masing-masing dan membuka pull request ke `main`. Ikuti format kode dengan Pint:

```bash
./vendor/bin/pint
```
