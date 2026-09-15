<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spot_business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained()->cascadeOnDelete();
            $table->string('day_type', 20);
            $table->boolean('is_closed')->default(false);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->timestamps();
            $table->unique(['parking_spot_id', 'day_type', 'opening_time', 'closing_time', 'is_closed'], 'parking_spot_business_hours_unique_schedule');
        });

        // 既存表示を変えずに新しい入力形式へ移せるよう、全日営業時間を初期レコードにする。
        DB::table('parking_spots')->orderBy('id')->each(function (object $parkingSpot): void {
            DB::table('parking_spot_business_hours')->insert([
                'parking_spot_id' => $parkingSpot->id,
                'day_type' => '全日',
                'is_closed' => false,
                'opening_time' => $parkingSpot->opening_time,
                'closing_time' => $parkingSpot->closing_time,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spot_business_hours');
    }
};
