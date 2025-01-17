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
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name', 255);
            $table->string('address', 255);
            $table->double('longitude', 8, 6);
            $table->double('latitude', 9, 6);
            $table->unsignedInteger('capacity')->nullable();
            $table->time('opening_time');
            $table->time('closing_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
