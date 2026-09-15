<?php

namespace Tests\Unit\Services;

use App\Models\ParkingSpot;
use App\Services\ParkingSpotDuplicateCandidateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingSpotDuplicateCandidateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_a_candidate_at_the_same_address(): void
    {
        $candidate = ParkingSpot::factory()->create(['name' => '別名称の駐輪場', 'address' => '東京都千代田区千代田1-1', 'latitude' => 35.681, 'longitude' => 139.767]);

        $candidates = app(ParkingSpotDuplicateCandidateService::class)->find('新規の駐輪場', '東京都千代田区千代田1-1', 35.69, 139.77);

        $this->assertTrue($candidates->contains($candidate));
    }

    public function test_it_finds_similar_names_within_the_configured_distance_only(): void
    {
        $nearby = ParkingSpot::factory()->create(['name' => '駅前 バイク駐輪場', 'latitude' => 35.6819, 'longitude' => 139.767]);
        $farAway = ParkingSpot::factory()->create(['name' => '駅前バイク駐輪場', 'latitude' => 35.683, 'longitude' => 139.767]);
        $differentName = ParkingSpot::factory()->create(['name' => '市役所駐輪場', 'latitude' => 35.6819, 'longitude' => 139.767]);

        $candidates = app(ParkingSpotDuplicateCandidateService::class)->find('駅前バイク駐輪場（北口）', '東京都千代田区丸の内1-1', 35.681, 139.767);

        $this->assertTrue($candidates->contains($nearby));
        $this->assertFalse($candidates->contains($farAway));
        $this->assertFalse($candidates->contains($differentName));
    }
}
