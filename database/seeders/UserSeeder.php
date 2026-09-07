<?php

namespace Database\Seeders;

use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prefectureIds = Prefecture::query()->pluck('id');

        if ($prefectureIds->isEmpty()) {
            throw new LogicException('UserSeeder requires at least one prefecture.');
        }

        User::factory(100)->create([
            'prefecture_id' => fn () => $prefectureIds->random(),
        ]);
    }
}
