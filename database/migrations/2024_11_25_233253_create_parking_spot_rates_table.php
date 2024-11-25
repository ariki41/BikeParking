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
        Schema::create('parking_spot_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained();
            $table->string('day_type');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('rate');
            $table->unsignedInteger('max_rate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spot_rates');
    }
};
