<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
            $table->dateTime('eaten_at');
            $table->string('title', 150);
            $table->enum('source', ['scan', 'manual']);
            $table->decimal('total_calories_kcal', 7, 1)->default(0);
            $table->decimal('total_protein_g', 6, 1)->default(0);
            $table->decimal('total_carbs_g', 6, 1)->default(0);
            $table->decimal('total_fat_g', 6, 1)->default(0);
            $table->decimal('total_fiber_g', 6, 1)->default(0);
            $table->decimal('total_sugar_g', 6, 1)->default(0);
            $table->decimal('total_sodium_mg', 7, 1)->default(0);
            $table->string('notes', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'eaten_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
