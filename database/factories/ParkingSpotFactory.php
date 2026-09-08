<?php

namespace Database\Factories;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingSpot>
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
            'user_id' => User::factory(),
            'name' => fake()->numerify('#####駐輪場'),
            'postalcode_id' => Postalcode::factory(),
            'address' => fake()->address(),
            'longitude' => fake()->randomFloat(6, 123, 146),
            'latitude' => fake()->randomFloat(6, 24, 46),
            'capacity' => fake()->numberBetween(1, 4),
            'max_displacement_class' => fake()->randomElement(EngineDisplacementClass::values()),
            'opening_time' => fake()->randomElement($openingTime),
            'closing_time' => fake()->randomElement($closingTime),
        ];
    }
}
