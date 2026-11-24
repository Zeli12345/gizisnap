<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->decimal('height_cm', 5, 1);
            $table->decimal('weight_kg', 5, 1);
            $table->enum('activity_level', ['sedentary', 'light', 'moderate', 'active', 'very_active']);
            $table->enum('goal', ['lose', 'maintain', 'gain']);
            $table->json('allergies')->nullable();
            $table->string('dietary_preference', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_profiles');
    }
};
