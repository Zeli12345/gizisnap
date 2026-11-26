<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    |
    | Akun Google dengan email ini otomatis menjadi admin saat login.
    |
    */

    'admin_email' => env('ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Kecerdasan buatan (AI)
    |--------------------------------------------------------------------------
    |
    | fake = true membuat aplikasi memakai jawaban contoh tanpa memanggil
    | penyedia AI. Hanya untuk pengembangan dan tes. Saat produksi, nilai ini
    | dipaksa false oleh AppServiceProvider supaya jawaban tiruan tidak pernah
    | sampai ke pengguna.
    |
    */

    'ai' => [
        'fake' => (bool) env('AI_FAKE', true),
        'provider' => env('AI_PROVIDER', 'openai'),
        'scan_model' => env('AI_SCAN_MODEL', 'gpt-6-luna'),
        'chat_model' => env('AI_CHAT_MODEL', 'gpt-6-luna'),
        'timeout' => (int) env('AI_TIMEOUT', 30),

        /*
        | Tingkat penalaran model. Hanya dikirim ke penyedia yang mengerti
        | (GiziSnap mengirimnya ke CommandCode saja). Nilai yang sah:
        | off, low, medium, high, xhigh, max. Kosongkan untuk memakai bawaan
        | penyedia. "max" memberi hasil gizi paling teliti.
        */
        'reasoning_effort' => env('AI_REASONING_EFFORT', 'max'),

        /*
        | Batas token jawaban. Scan memakai keluaran JSON panjang, chat
        | cukup pendek. Kosongkan untuk memakai bawaan penyedia.
        */
        'scan_max_tokens' => (int) env('AI_SCAN_MAX_TOKENS', 4096),
        'chat_max_tokens' => (int) env('AI_CHAT_MAX_TOKENS', 1500),

        'daily_scan_limit' => (int) env('AI_DAILY_SCAN_LIMIT', 10),
        'daily_chat_limit' => (int) env('AI_DAILY_CHAT_LIMIT', 30),
        'per_minute_limit' => (int) env('AI_PER_MINUTE_LIMIT', 5),
        'chat_history' => 10,
        'low_confidence' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Foto
    |--------------------------------------------------------------------------
    */

    'scan' => [
        'disk' => 'local',
        'directory' => 'scans',
        'max_upload_kb' => 5120,
        'max_side_px' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rumus target harian
    |--------------------------------------------------------------------------
    |
    | Energi istirahat memakai rumus Mifflin-St Jeor. Angka di bawah adalah
    | nilai awal; minta guru atau tenaga gizi memeriksanya.
    |
    */

    'targets' => [
        'activity_factors' => [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9,
        ],
        'goal_adjustments' => [
            'lose' => -0.15,
            'maintain' => 0.0,
            'gain' => 0.10,
        ],
        'macro_split' => [
            'protein' => 0.20,
            'fat' => 0.25,
            'carbs' => 0.55,
        ],
        'water_ml' => 2000,
        'adult_age' => 18,
    ],

];
