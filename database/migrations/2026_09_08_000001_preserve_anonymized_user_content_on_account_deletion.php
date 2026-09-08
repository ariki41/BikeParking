<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retired_user_ids', function (Blueprint $table): void {
            $table->id();
            $table->char('user_id_hash', 64)->unique();
            $table->timestamps();
        });

        $this->replaceUserForeignKey('parking_spots', 'set null');
        $this->replaceUserForeignKey('reviews', 'set null');
    }

    public function down(): void
    {
        // Anonymized records cannot recover their original user IDs, so retain nullable columns.
        $this->replaceUserForeignKey('parking_spots', 'cascade');
        $this->replaceUserForeignKey('reviews', 'cascade');

        Schema::dropIfExists('retired_user_ids');
    }

    private function replaceUserForeignKey(string $tableName, string $deleteAction): void
    {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table($tableName, function (Blueprint $table) use ($deleteAction): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete($deleteAction);
        });
    }
};
