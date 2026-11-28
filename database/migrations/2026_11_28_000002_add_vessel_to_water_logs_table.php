<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('water_logs', function (Blueprint $table) {
            $table->string('vessel', 20)->nullable()->after('amount_ml');
        });
    }

    public function down(): void
    {
        Schema::table('water_logs', function (Blueprint $table) {
            $table->dropColumn('vessel');
        });
    }
};
