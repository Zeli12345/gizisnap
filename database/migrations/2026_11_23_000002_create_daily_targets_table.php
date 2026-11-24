<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('calories_kcal');
            $table->decimal('protein_g', 5, 1);
            $table->decimal('carbs_g', 5, 1);
            $table->decimal('fat_g', 5, 1);
            $table->unsignedSmallInteger('water_ml')->default(2000);
            $table->enum('source', ['auto', 'manual'])->default('auto');
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['user_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_targets');
    }
};
