<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Prefecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefecture_id' => Prefecture::factory(),
            'name' => fake()->unique()->city(),
            'name_kana' => fake()->unique()->city(),
        ];
    }
}
