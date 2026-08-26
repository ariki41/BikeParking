<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParkingSpotPostalcodeRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parking_spots_schema_uses_postalcode_id_foreign_key_and_index(): void
    {
        $this->assertTrue(Schema::hasColumn('parking_spots', 'postalcode_id'));
        $this->assertFalse(Schema::hasColumn('parking_spots', 'postalcode'));

        $index = collect(Schema::getIndexes('parking_spots'))
            ->first(fn (array $index): bool => $index['columns'] === ['postalcode_id']);
        $foreignKey = collect(Schema::getForeignKeys('parking_spots'))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['postalcode_id']);

        $this->assertNotNull($index);
        $this->assertNotNull($foreignKey);
        $this->assertSame('postalcodes', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
    }

    public function test_parking_spot_and_postalcode_relationships_expose_address_information(): void
    {
        $prefecture = Prefecture::create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $city = City::create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);
        $postalcode = Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
        $user = User::factory()->create(['prefecture_id' => $prefecture->id]);
        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '郵便番号リレーションテスト駐輪場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        $this->assertTrue($parkingSpot->postalcode->is($postalcode));
        $this->assertSame('東京都千代田区千代田', $parkingSpot->postalcode->fullAddress());
        $this->assertTrue($postalcode->parkingSpots->contains($parkingSpot));
    }
}
