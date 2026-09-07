<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apply the current ownership and dependent-data rules to existing databases.
     */
    public function up(): void
    {
        $this->removeDuplicates('parking_spot_tags', ['parking_spot_id', 'tag_id']);
        $this->removeDuplicates('parking_spot_rates', [
            'parking_spot_id',
            'day_type',
            'start_time',
            'end_time',
        ]);

        DB::table('parking_spot_rates')
            ->where('max_rate', 0)
            ->update(['max_rate' => null]);

        DB::table('sessions')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'sessions.user_id');
            })
            ->update(['user_id' => null]);

        Schema::table('parking_spot_tags', function (Blueprint $table): void {
            $table->unique(
                ['parking_spot_id', 'tag_id'],
                'parking_spot_tags_spot_tag_unique',
            );
        });

        Schema::table('parking_spot_rates', function (Blueprint $table): void {
            $table->unique(
                ['parking_spot_id', 'day_type', 'start_time', 'end_time'],
                'parking_spot_rates_schedule_unique',
            );
        });

        Schema::table('parking_spots', function (Blueprint $table): void {
            $table->index(['latitude', 'longitude'], 'parking_spots_location_index');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        $this->replaceForeignKey('parking_spots', 'user_id', 'users', 'cascade');
        $this->replaceForeignKey('favorites', 'user_id', 'users', 'cascade');
        $this->replaceForeignKey('favorites', 'parking_spot_id', 'parking_spots', 'cascade');
        $this->replaceForeignKey('reviews', 'user_id', 'users', 'cascade');
        $this->replaceForeignKey('reviews', 'parking_spot_id', 'parking_spots', 'cascade');
        $this->replaceForeignKey('parking_spot_tags', 'parking_spot_id', 'parking_spots', 'cascade');
        $this->replaceForeignKey('parking_spot_tags', 'tag_id', 'tags', 'cascade');
        $this->replaceForeignKey('parking_spot_rates', 'parking_spot_id', 'parking_spots', 'cascade');
    }

    public function down(): void
    {
        // MySQL may replace its implicit foreign-key indexes with the new unique indexes.
        // Restore the original indexes before removing those unique indexes.
        $this->ensureIndex(
            'parking_spot_rates',
            ['parking_spot_id'],
            'parking_spot_rates_parking_spot_id_foreign',
        );
        $this->ensureIndex(
            'parking_spot_tags',
            ['parking_spot_id'],
            'parking_spot_tags_parking_spot_id_foreign',
        );

        $this->replaceForeignKey('parking_spots', 'user_id', 'users', 'no action');
        $this->replaceForeignKey('favorites', 'user_id', 'users', 'no action');
        $this->replaceForeignKey('favorites', 'parking_spot_id', 'parking_spots', 'no action');
        $this->replaceForeignKey('reviews', 'user_id', 'users', 'no action');
        $this->replaceForeignKey('reviews', 'parking_spot_id', 'parking_spots', 'no action');
        $this->replaceForeignKey('parking_spot_tags', 'parking_spot_id', 'parking_spots', 'no action');
        $this->replaceForeignKey('parking_spot_tags', 'tag_id', 'tags', 'no action');
        $this->replaceForeignKey('parking_spot_rates', 'parking_spot_id', 'parking_spots', 'no action');

        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('parking_spots', function (Blueprint $table): void {
            $table->dropIndex('parking_spots_location_index');
        });

        Schema::table('parking_spot_rates', function (Blueprint $table): void {
            $table->dropUnique('parking_spot_rates_schedule_unique');
        });

        Schema::table('parking_spot_tags', function (Blueprint $table): void {
            $table->dropUnique('parking_spot_tags_spot_tag_unique');
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function removeDuplicates(string $table, array $columns): void
    {
        DB::table($table)
            ->select($columns)
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate) use ($table, $columns): void {
                $query = DB::table($table);

                foreach ($columns as $column) {
                    $query->where($column, $duplicate->{$column});
                }

                $duplicateIds = $query
                    ->orderBy('id')
                    ->pluck('id')
                    ->slice(1);

                DB::table($table)->whereIn('id', $duplicateIds)->delete();
            });
    }

    private function replaceForeignKey(
        string $tableName,
        string $column,
        string $referencedTable,
        string $deleteAction,
    ): void {
        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->dropForeign([$column]);
        });

        Schema::table($tableName, function (Blueprint $table) use (
            $column,
            $referencedTable,
            $deleteAction,
        ): void {
            $table->foreign($column)
                ->references('id')
                ->on($referencedTable)
                ->onDelete($deleteAction);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureIndex(string $tableName, array $columns, string $indexName): void
    {
        $indexExists = collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => $index['name'] === $indexName);

        if ($indexExists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }
};
