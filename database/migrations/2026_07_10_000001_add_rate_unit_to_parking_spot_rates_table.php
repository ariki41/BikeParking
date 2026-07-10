<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_spot_rates', function (Blueprint $table) {
            $table->unsignedInteger('unit_minutes')->default(30)->after('end_time');
            $table->unsignedInteger('free_minutes')->default(0)->after('rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_spot_rates', function (Blueprint $table) {
            $table->dropColumn(['unit_minutes', 'free_minutes']);
        });
    }
};
