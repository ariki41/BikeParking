<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeafletMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_the_shared_map_with_livewire_events(): void
    {
        $response = $this->get(route('search', [
            'lat' => '35.681167',
            'lon' => '139.767052',
        ]))->assertOk();

        $html = html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5);

        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.css'));
        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.js'));
        $this->assertStringContainsString('data-leaflet-map', $html);
        $this->assertStringContainsString('"center":{"latitude":35.681167,"longitude":139.767052}', $html);
        $this->assertStringContainsString('"boundsEvent":"updateBounds"', $html);
        $this->assertStringContainsString('"markersEvent":"displayMarkers"', $html);
        $this->assertStringContainsString('parking-spots\/__ID__', $html);
        $this->assertStringContainsString('地図を読み込めませんでした。', $html);
        $this->assertStringNotContainsString('window.onload', $html);
        $this->assertStringNotContainsString('window.markers', $html);
    }

    public function test_parking_spot_detail_configures_a_static_marker(): void
    {
        $parkingSpot = $this->createParkingSpot();

        $response = $this->get(route('parking_spot.show', $parkingSpot))->assertOk();
        $html = html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5);

        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.css'));
        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.js'));
        $this->assertStringContainsString('"center":{"latitude":35.685,"longitude":139.753}', $html);
        $this->assertStringContainsString('"zoom":17', $html);
        $this->assertStringContainsString('"markers":[{"latitude":35.685,"longitude":139.753}]', $html);
        $this->assertStringNotContainsString('window.onload', $html);
    }

    private function createParkingSpot(): ParkingSpot
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
        $owner = User::factory()->create(['prefecture_id' => $prefecture->id]);

        return ParkingSpot::forceCreate([
            'user_id' => $owner->id,
            'name' => '地図テスト駐輪場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);
    }
}
