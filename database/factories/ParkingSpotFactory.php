<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParkingSpot>
 */
class ParkingSpotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openingTime = ['00:00:00', '06:00:00', '09:00:00'];
        $closingTime = ['18:00:00', '21:00:00', '00:00:00'];

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'name' => fake()->numerify('#####駐車場'),
            'address' => fake()->address(),
            'longitude' => fake()->longitude(122.93457, 153.986672),
            'latitude' => fake()->latitude(24.396308, 45.551483),
            'capacity' => fake()->numberBetween(1, 100),
            'opening_time' => fake()->randomElement($openingTime),
            'closing_time' => fake()->randomElement($closingTime),
        ];
    }
}
