<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->index()->after('prefecture_id');
        });

        Schema::table('parking_spots', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->index()->after('user_id');
        });

        Schema::create('parking_spot_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parking_spot_update_history_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('parking_spot_update_history_id', 'psr_history_fk')
                ->references('id')->on('parking_spot_update_histories')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('parking_spot_moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_spot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parking_spot_update_history_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->foreign('parking_spot_update_history_id', 'psma_history_fk')
                ->references('id')->on('parking_spot_update_histories')->nullOnDelete();
            $table->index(['parking_spot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spot_moderation_actions');
        Schema::dropIfExists('parking_spot_reports');

        Schema::table('parking_spots', function (Blueprint $table) {
            $table->dropIndex(['is_published']);
            $table->dropColumn('is_published');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropColumn('is_admin');
        });
    }
};
