<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 255);
            $table->enum('status', ['pending', 'completed', 'failed', 'not_food'])->default('pending');
            $table->string('dish_name', 150)->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->json('ai_result')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
