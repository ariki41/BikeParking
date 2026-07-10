<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_spot_rates', function ($table) {
            $table->unsignedInteger('max_rate')->nullable()->change();
        });

        DB::table('parking_spot_rates')
            ->where('max_rate', 0)
            ->update(['max_rate' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('parking_spot_rates')
            ->whereNull('max_rate')
            ->update(['max_rate' => 0]);

        Schema::table('parking_spot_rates', function ($table) {
            $table->unsignedInteger('max_rate')->nullable(false)->change();
        });
    }
};
