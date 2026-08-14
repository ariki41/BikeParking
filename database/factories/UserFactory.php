<?php

namespace Database\Factories;

use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->userName(5),
            'name' => fake()->name(),
            'password' => static::$password ??= Hash::make('password'),
            'prefecture_id' => Prefecture::factory(),
            'remember_token' => Str::random(10),
        ];
    }
}
