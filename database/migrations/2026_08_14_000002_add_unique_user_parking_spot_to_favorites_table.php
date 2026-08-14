<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('favorites')
            ->select('user_id', 'parking_spot_id')
            ->groupBy('user_id', 'parking_spot_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $favorite): void {
                $duplicateIds = DB::table('favorites')
                    ->where('user_id', $favorite->user_id)
                    ->where('parking_spot_id', $favorite->parking_spot_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->slice(1);

                DB::table('favorites')->whereIn('id', $duplicateIds)->delete();
            });

        Schema::table('favorites', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'parking_spot_id'],
                'favorites_user_parking_spot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropUnique('favorites_user_parking_spot_unique');
        });
    }
};
