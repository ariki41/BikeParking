<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
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
        $this->assertDatabaseCount('parking_spots', 4);
        $this->assertDatabaseCount('parking_spot_rates', 6);
        $this->assertDatabaseCount('reviews', 4);
        $this->assertEqualsCanonicalizing(
            EngineDisplacementClass::values(),
            ParkingSpot::query()
                ->get(['max_displacement_class'])
                ->pluck('max_displacement_class')
                ->map(fn (EngineDisplacementClass $class): string => $class->value)
                ->all(),
        );

        $this->seed(DevelopmentSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('parking_spots', 4);
        $this->assertDatabaseCount('parking_spot_rates', 6);
        $this->assertDatabaseCount('reviews', 4);
        $this->assertSame(4, ParkingSpot::query()->where('user_id', $owner->id)->count());
        $this->assertSame(6, ParkingSpotRates::query()->count());
        $this->assertSame(4, Review::query()->count());
        $this->assertSame(0, ParkingSpot::query()->whereNull('max_displacement_class')->count());
    }

    public function test_it_requires_a_password_for_development_users(): void
    {
        config()->set('development.seed_password', null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DEVELOPMENT_SEED_PASSWORD must be configured');

        $this->seed(DevelopmentSeeder::class);
    }
}
