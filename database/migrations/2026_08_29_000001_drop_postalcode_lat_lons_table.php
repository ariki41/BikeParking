<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('postalcode_lat_lons');
    }

    public function down(): void
    {
        Schema::create('postalcode_lat_lons', function ($table) {
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
};
