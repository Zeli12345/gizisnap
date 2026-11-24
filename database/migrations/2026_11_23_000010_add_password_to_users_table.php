<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login dengan email dan kata sandi, di samping login Google.
 *
 * Kolom dikumpulkan dalam satu migration karena sama-sama menopang
 * autentikasi manual: kata sandi, lupa sandi, dan verifikasi email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable: akun yang hanya masuk lewat Google tidak punya kata sandi.
            $table->string('password')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password', 'email_verified_at']);
        });
    }
};
