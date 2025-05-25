<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PostalcodeLatLon;

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

        $postalcodes = $this->getRandomPostalCode();
        $postalcode = $postalcodes->postalcode_id;
        $prefecture = $postalcodes->prefecture;
        $city = $postalcodes->city;
        $town = $postalcodes->town;
        $address = $prefecture . $city . $town;
        $latitude = $postalcodes->latitude;
        $longitude = $postalcodes->longitude;

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'name' => fake()->numerify('#####駐車場'),
            'postalcode' => $postalcode,
            'address' => $address,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'capacity' => fake()->numberBetween(1, 100),
            'opening_time' => fake()->randomElement($openingTime),
            'closing_time' => fake()->randomElement($closingTime),
        ];
    }

    /**
     * 郵便番号をランダムに取得する
     * 
     * @return PostalcodeLatLon
     */
    private function getRandomPostalCode(): PostalcodeLatLon
    {
        $postalcode = PostalcodeLatLon::join('postalcodes', 'postalcode_lat_lons.postalcode', '=', 'postalcodes.postalcode')
            ->select('postalcode_lat_lons.*', 'postalcodes.id as postalcode_id')
            ->inRandomOrder()
            ->first();
        
        return $postalcode;
    }
}
