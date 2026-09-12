<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_spot_images', function (Blueprint $table): void {
            // Existing images do not have a reliably known contributor, so retain them as unassigned.
            $table->foreignId('user_id')
                ->nullable()
                ->after('parking_spot_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parking_spot_images', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
