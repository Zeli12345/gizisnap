<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_days');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('fingerprint', 64);
            $table->string('status', 20)->default('ready');
            $table->json('content')->nullable();
            $table->string('model', 60)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_days', 'period_end']);
            $table->index(['user_id', 'period_days', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_reports');
    }
};
