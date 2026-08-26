<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_spots', function (Blueprint $table) {
            $table->renameColumn('postalcode', 'postalcode_id');
        });

        Schema::table('parking_spots', function (Blueprint $table) {
            $table->unsignedBigInteger('postalcode_id')->change();
            $table->index('postalcode_id');
            $table->foreign('postalcode_id')->references('id')->on('postalcodes');
        });
    }

    public function down(): void
    {
        Schema::table('parking_spots', function (Blueprint $table) {
            $table->dropForeign(['postalcode_id']);
            $table->dropIndex(['postalcode_id']);
            $table->integer('postalcode_id')->change();
        });

        Schema::table('parking_spots', function (Blueprint $table) {
            $table->renameColumn('postalcode_id', 'postalcode');
        });
    }
};
