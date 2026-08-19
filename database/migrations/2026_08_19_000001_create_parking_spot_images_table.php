<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spot_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['parking_spot_id', 'position']);
            $table->unique(['parking_spot_id', 'path']);
        });

        DB::table('parking_spots')
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($parkingSpots): void {
                $now = now();
                $images = $parkingSpots->map(fn ($parkingSpot) => [
                    'parking_spot_id' => $parkingSpot->id,
                    'path' => $parkingSpot->image_path,
                    'position' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('parking_spot_images')->insert($images);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spot_images');
    }
};
