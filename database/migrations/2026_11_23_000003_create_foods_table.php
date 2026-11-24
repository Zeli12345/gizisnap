<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->index();
            $table->string('category', 50);
            $table->string('serving_desc', 80);
            $table->decimal('serving_weight_g', 6, 1);
            $table->decimal('calories_kcal', 6, 1);
            $table->decimal('protein_g', 5, 1);
            $table->decimal('carbs_g', 5, 1);
            $table->decimal('fat_g', 5, 1);
            $table->decimal('fiber_g', 5, 1)->nullable();
            $table->decimal('sugar_g', 5, 1)->nullable();
            $table->decimal('sodium_mg', 6, 1)->nullable();
            $table->string('source', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
