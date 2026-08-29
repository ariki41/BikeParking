<?php

namespace Database\Factories;

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

        $postalcode = $this->getRandomPostalCode();
        $address = $postalcode->city->prefecture->name.$postalcode->city->name.$postalcode->name;

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'name' => fake()->numerify('#####駐車場'),
            'postalcode_id' => $postalcode->id,
            'address' => $address,
            'longitude' => fake()->randomFloat(6, 123, 146),
            'latitude' => fake()->randomFloat(6, 24, 46),
            'capacity' => fake()->numberBetween(1, 4),
            'opening_time' => fake()->randomElement($openingTime),
            'closing_time' => fake()->randomElement($closingTime),
        ];
    }

    /**
     * 郵便番号をランダムに取得する
     */
    private function getRandomPostalCode(): Postalcode
    {
        return Postalcode::query()
            ->with('city.prefecture')
            ->active()
            ->inRandomOrder()
            ->firstOrFail();
    }
}
