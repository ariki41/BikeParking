<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Postalcode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postalcode>
 */
class PostalcodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'postalcode' => fake()->unique()->numerify('#######'),
            'city_id' => City::factory(),
            'name' => fake()->streetName(),
            'name_kana' => fake()->streetName(),
            'is_active' => true,
        ];
    }
}
