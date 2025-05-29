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
        Schema::create('postalcode_lat_lons', function (Blueprint $table) {
            $table->id();
            $table->string('postalcode');
            $table->string('prefecture');
            $table->string('city');
            $table->string('town');
            $table->double('latitude', 9, 6);
            $table->double('longitude', 8, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postalcode_lat_lons');
    }
};
