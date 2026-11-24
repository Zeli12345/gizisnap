<?php

// Konfigurasi GiziSnap. Semua angka aplikasi diatur di sini dan dibaca lewat
// config('gizisnap.xxx'). Jangan memanggil env() di luar berkas config.

return [

    // AI: penyedia, model, token, dan batas pemakaian
    'ai' => [
        // true = pakai jawaban tiruan tanpa memanggil penyedia AI (untuk tes/dev)
        'fake' => (bool) env('AI_FAKE', false),

        // Penyedia AI sesuai stack proyek
        'provider' => env('AI_PROVIDER', 'commandcode'),

        // Model untuk scan foto makanan dan chat asisten
        'scan_model' => env('AI_SCAN_MODEL', 'deepseek/deepseek-v4.1-flash'),
        'chat_model' => env('AI_CHAT_MODEL', 'deepseek/deepseek-v4.1-flash'),

        // Tingkat penalaran model
        'reasoning_effort' => env('AI_REASONING_EFFORT', 'max'),

        // Batas waktu tiap panggilan AI (detik)
        'timeout' => (int) env('AI_TIMEOUT', 120),

        // Batas token keluaran per panggilan
        'scan_max_tokens' => 1024,
        'chat_max_tokens' => 1024,

        // Batas pemakaian harian per pengguna
        'daily_scan_limit' => (int) env('AI_DAILY_SCAN_LIMIT', 10),
        'daily_chat_limit' => (int) env('AI_DAILY_CHAT_LIMIT', 30),

        // Batas permintaan per menit per pengguna (anti spam)
        'per_minute_limit' => (int) env('AI_PER_MINUTE_LIMIT', 5),

        // Jumlah pesan lampau yang diikutkan sebagai konteks chat
        'chat_history' => 10,

        // Ambang keyakinan hasil scan (0-1); di bawah ini dianggap kurang yakin
        'low_confidence' => 0.5,
    ],

    // Scan: penyimpanan foto makanan
    'scan' => [
        // Disk dan folder untuk foto hasil scan
        'disk' => 'public',
        'directory' => 'scans',

        // Ukuran unggahan maksimal (KB)
        'max_upload_kb' => 4096,

        // Sisi terpanjang gambar dipangkas ke ukuran ini sebelum dikirim ke AI (px)
        'max_side_px' => 1600,
    ],

    // Targets: dasar perhitungan target gizi harian
    'targets' => [
        // Pengali tingkat aktivitas untuk rumus kalori (Mifflin-St Jeor)
        'activity_factors' => [
            'tidak_aktif' => 1.2,
            'ringan' => 1.375,
            'sedang' => 1.55,
            'berat' => 1.725,
        ],

        // Penyesuaian kalori harian per tujuan (kcal)
        'goal_adjustments' => [
            'turun' => -500,
            'tahan' => 0,
            'naik' => 500,
        ],

        // Bagian makro terhadap total kalori (jumlah = 1.0)
        'macro_split' => [
            'protein' => 0.2,
            'karbohidrat' => 0.55,
            'lemak' => 0.25,
        ],

        // Target air minum harian bawaan (ml)
        'water_ml' => 2000,

        // Usia minimum yang dianggap dewasa untuk perhitungan target
        'adult_age' => 18,
    ],

    // Email admin untuk pemberitahuan penting (dari .env, lihat ADMIN_EMAIL)
    'admin_email' => env('ADMIN_EMAIL'),
];
