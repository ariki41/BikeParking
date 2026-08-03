<?php

namespace Database\Factories;

use App\Models\Prefecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prefecture>
 */
class PrefectureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().'県',
            'name_kana' => mb_convert_kana(fake()->unique()->word(), 'KAS'),
        ];
    }
}
