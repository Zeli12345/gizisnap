<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('visited_on');
            $table->timestamps();

            // Satu baris per pengguna per hari, jadi kunjungan di hari yang sama
            // tidak dihitung berkali-kali.
            $table->unique(['user_id', 'visited_on']);
            $table->index('visited_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_visits');
    }
};
