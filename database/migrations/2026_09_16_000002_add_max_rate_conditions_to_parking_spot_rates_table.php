<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_spot_rates', function (Blueprint $table): void {
            // Existing prices do not contain enough information to infer their terms.
            $table->string('max_rate_period')->nullable()->after('max_rate');
            $table->boolean('max_rate_repeats')->nullable()->after('max_rate_period');
        });
    }

    public function down(): void
    {
        Schema::table('parking_spot_rates', function (Blueprint $table): void {
            $table->dropColumn(['max_rate_period', 'max_rate_repeats']);
        });
    }
};
