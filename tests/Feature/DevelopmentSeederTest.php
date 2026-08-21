<?php

namespace Tests\Feature;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_development_data_without_removing_existing_records_or_creating_duplicates(): void
    {
        config()->set('development.seed_password', 'development-test-password');
        $existingUser = User::factory()->create();

        $this->seed(DevelopmentSeeder::class);

        $owner = User::query()->where('user_id', 'development-owner')->firstOrFail();
        $reviewer = User::query()->where('user_id', 'development-reviewer')->firstOrFail();

        $this->assertTrue(Hash::check('development-test-password', $owner->password));
        $this->assertTrue(Hash::check('development-test-password', $reviewer->password));
        $this->assertDatabaseHas('users', ['id' => $existingUser->id]);
        $this->assertDatabaseCount('parking_spots', 3);
        $this->assertDatabaseCount('parking_spot_rates', 5);
        $this->assertDatabaseCount('reviews', 3);

        $this->seed(DevelopmentSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('parking_spots', 3);
        $this->assertDatabaseCount('parking_spot_rates', 5);
        $this->assertDatabaseCount('reviews', 3);
        $this->assertSame(3, ParkingSpot::query()->where('user_id', $owner->id)->count());
        $this->assertSame(5, ParkingSpotRates::query()->count());
        $this->assertSame(3, Review::query()->count());
    }

    public function test_it_requires_a_password_for_development_users(): void
    {
        config()->set('development.seed_password', null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DEVELOPMENT_SEED_PASSWORD must be configured');

        $this->seed(DevelopmentSeeder::class);
    }
}
