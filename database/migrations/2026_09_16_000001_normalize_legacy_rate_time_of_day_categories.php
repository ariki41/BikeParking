<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy labels only described a time period. Preserve that period and make
        // their previously implicit all-days applicability explicit.
        DB::table('parking_spot_rates')
            ->whereIn('day_type', ['昼間', '夜間'])
            ->update(['day_type' => '全日']);
    }

    public function down(): void
    {
        // The original label cannot be reconstructed after normalization.
    }
};
