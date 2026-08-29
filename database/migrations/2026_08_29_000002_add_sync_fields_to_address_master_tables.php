<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prefectures', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->unique(['prefecture_id', 'name']);
        });

        Schema::table('postalcodes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name_kana');
            $table->unique(['postalcode', 'city_id']);
            $table->index(['postalcode', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('postalcodes', function (Blueprint $table) {
            $table->dropIndex(['postalcode', 'is_active']);
            $table->dropUnique(['postalcode', 'city_id']);
            $table->dropColumn('is_active');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique(['prefecture_id', 'name']);
        });

        Schema::table('prefectures', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
