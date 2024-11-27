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
        Schema::create('postalcodes', function (Blueprint $table) {
            $table->id();
            $table->string('postalcode', 8);
            $table->foreignId('city_id')->constrained();
            $table->string('name', 1000);
            $table->string('name_kana', 1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postalcodes');
    }
};
