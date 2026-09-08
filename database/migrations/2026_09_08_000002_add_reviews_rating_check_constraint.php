<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Enforce the rating range even for imports and other writes that bypass validation.
     */
    public function up(): void
    {
        // Preserve existing reviews by bringing legacy invalid values into the documented range.
        DB::table('reviews')->where('rating', '<', 1)->update(['rating' => 1]);
        DB::table('reviews')->where('rating', '>', 5)->update(['rating' => 5]);

        DB::statement(
            'ALTER TABLE reviews ADD CONSTRAINT reviews_rating_between_one_and_five CHECK (rating BETWEEN 1 AND 5)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reviews DROP CHECK reviews_rating_between_one_and_five');
    }
};
