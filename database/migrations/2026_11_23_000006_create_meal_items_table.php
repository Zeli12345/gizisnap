<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->string('name', 120);
            $table->string('portion_desc', 80);
            $table->decimal('weight_g', 6, 1)->nullable();
            $table->decimal('calories_kcal', 6, 1);
            $table->decimal('protein_g', 5, 1);
            $table->decimal('carbs_g', 5, 1);
            $table->decimal('fat_g', 5, 1);
            $table->decimal('fiber_g', 5, 1)->default(0);
            $table->decimal('sugar_g', 5, 1)->default(0);
            $table->decimal('sodium_mg', 6, 1)->default(0);
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_items');
    }
};
