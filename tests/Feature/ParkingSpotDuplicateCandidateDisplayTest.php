<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParkingSpotDuplicateCandidateDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_confirmation_displays_duplicate_candidates_without_blocking_registration(): void
    {
        $user = User::factory()->create();
        $postalcode = Postalcode::factory()->create();
        $address = $postalcode->fullAddress().'1-2';
        $candidate = ParkingSpot::factory()->create([
            'name' => '駅前 バイク駐輪場',
            'address' => $address,
            'latitude' => 35.685,
            'longitude' => 139.753,
        ]);
        Http::fake(['*' => Http::response([
            'Feature' => [[
                'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                'Property' => ['Address' => $address],
            ]],
        ])]);

        $this->actingAs($user)->get(route('parking_spot.create'))->assertOk();

        $this->post(route('parking_spot.confirm'), $this->input($postalcode, $postalcode->fullAddress()))
            ->assertOk()
            ->assertSee('重複している可能性がある駐輪場')
            ->assertSee($candidate->name)
            ->assertSee(route('parking_spot.show', $candidate), false)
            ->assertSee('action="'.route('parking_spot.store').'"', false);
    }

    private function input(Postalcode $postalcode, string $address1): array
    {
        return [
            'name' => '駅前バイク駐輪場（北口）',
            'postalcode' => $postalcode->postalcode,
            'address1' => $address1,
            'address2' => '1-2',
            'capacity' => 1,
            'max_displacement_class' => EngineDisplacementClass::UpTo50cc->value,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [[
                'day_type' => '全日',
                'start_time' => '00:00',
                'end_time' => '00:00',
                'unit_minutes' => 30,
                'rate' => 100,
                'free_minutes' => 0,
                'no_free_minutes' => '1',
                'max_rate' => 1000,
                'no_max_rate' => '0',
            ]],
        ];
    }
}
