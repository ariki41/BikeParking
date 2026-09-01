<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ParkingSpotDisplacementClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_nullable_indexed_max_displacement_class_for_existing_records(): void
    {
        $this->assertTrue(Schema::hasColumn('parking_spots', 'max_displacement_class'));

        $index = collect(Schema::getIndexes('parking_spots'))
            ->first(fn (array $index): bool => $index['columns'] === ['max_displacement_class']);

        $this->assertNotNull($index);

        [$user, $postalcode] = $this->createUserAndPostalcode();
        $parkingSpot = $this->createParkingSpot($user, $postalcode, '区分未設定の既存駐輪場', null);

        $this->assertNull($parkingSpot->max_displacement_class);
        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('駐車可能な排気量')
            ->assertSee('grid-cols-[140px_minmax(0,1fr)]', false)
            ->assertSee('未設定');
    }

    public function test_create_requires_a_valid_class_and_persists_it_through_confirmation(): void
    {
        [$user, $postalcode] = $this->createUserAndPostalcode();
        $createUrl = route('parking_spot.create');

        $this->actingAs($user)
            ->get($createUrl)
            ->assertOk()
            ->assertSee('name="max_displacement_class"', false)
            ->assertSee('原付（50cc以下）')
            ->assertSee('大型（400cc超を含む）');

        $missingClassInput = $this->formInput($postalcode);
        unset($missingClassInput['max_displacement_class']);

        $this->from($createUrl)
            ->post(route('parking_spot.confirm'), $missingClassInput)
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors(['max_displacement_class']);

        $this->from($createUrl)
            ->post(route('parking_spot.confirm'), $this->formInput($postalcode, [
                'max_displacement_class' => 'invalid-class',
            ]))
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors(['max_displacement_class']);

        Http::fake([
            '*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都千代田区千代田1-2'],
                ]],
            ]),
        ]);

        $this->get($createUrl)->assertOk();

        $this->post(route('parking_spot.confirm'), $this->formInput($postalcode, [
            'max_displacement_class' => EngineDisplacementClass::UpTo400cc->value,
        ]))
            ->assertOk()
            ->assertSee('中型（400cc以下）');

        $this->post(route('parking_spot.store'), ['back' => 'back'])
            ->assertRedirect($createUrl);

        $backResponse = $this->get($createUrl)->assertOk();
        $this->assertMatchesRegularExpression(
            '/<option value="up_to_400cc"\s+selected>/',
            $backResponse->getContent(),
        );

        $this->post(route('parking_spot.store'))
            ->assertRedirect(route('home'));

        $parkingSpot = ParkingSpot::query()->where('name', '排気量区分テスト駐輪場')->sole();

        $this->assertSame(EngineDisplacementClass::UpTo400cc, $parkingSpot->max_displacement_class);
    }

    public function test_edit_retains_updates_and_records_the_class_in_history(): void
    {
        [$user, $postalcode] = $this->createUserAndPostalcode();
        $parkingSpot = $this->createParkingSpot(
            $user,
            $postalcode,
            '排気量区分編集テスト駐輪場',
            EngineDisplacementClass::UpTo50cc,
        );

        $editResponse = $this->actingAs($user)
            ->get(route('parking_spot.edit', $parkingSpot))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<option value="up_to_50cc"\s+selected>/',
            $editResponse->getContent(),
        );

        $input = [
            'id' => $parkingSpot->id,
            'name' => $parkingSpot->name,
            'postalcode' => $postalcode->postalcode,
            'address' => $parkingSpot->address,
            'longitude' => $parkingSpot->longitude,
            'latitude' => $parkingSpot->latitude,
            'capacity' => $parkingSpot->capacity,
            'max_displacement_class' => EngineDisplacementClass::Over400cc->value,
            'image_paths' => [],
            'image_path' => null,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->rateInput()],
        ];

        $this->withSession([
            ParkingSpotConfirmationService::SESSION_KEY => [
                'mode' => ParkingSpotConfirmationService::MODE_EDIT,
                'parking_spot_id' => $parkingSpot->id,
                'input' => $input,
                'temporary_image_paths' => [],
                'expires_at' => now()->addDay()->getTimestamp(),
            ],
        ])->put(route('parking_spot.update', $parkingSpot))
            ->assertRedirect(route('home'));

        $this->assertSame(
            EngineDisplacementClass::Over400cc,
            $parkingSpot->refresh()->max_displacement_class,
        );

        $history = ParkingSpotUpdateHistory::sole();
        $this->assertSame('up_to_50cc', $history->changes['max_displacement_class']['before']);
        $this->assertSame('over_400cc', $history->changes['max_displacement_class']['after']);
        $this->assertStringContainsString('駐車可能な排気量区分', $history->change_summary);

        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('大型（400cc超を含む）');
    }

    public function test_search_filters_list_and_markers_by_selected_class_or_higher(): void
    {
        [$user, $postalcode] = $this->createUserAndPostalcode();
        $classes = [
            '50cc対応' => EngineDisplacementClass::UpTo50cc,
            '125cc対応' => EngineDisplacementClass::UpTo125cc,
            '400cc対応' => EngineDisplacementClass::UpTo400cc,
            '大型対応' => EngineDisplacementClass::Over400cc,
            '区分未設定' => null,
        ];

        foreach ($classes as $name => $class) {
            $this->createParkingSpot($user, $postalcode, $name, $class);
        }

        $expectedNamesBySelection = [
            EngineDisplacementClass::UpTo50cc->value => ['50cc対応', '125cc対応', '400cc対応', '大型対応'],
            EngineDisplacementClass::UpTo125cc->value => ['125cc対応', '400cc対応', '大型対応'],
            EngineDisplacementClass::UpTo400cc->value => ['400cc対応', '大型対応'],
            EngineDisplacementClass::Over400cc->value => ['大型対応'],
        ];

        foreach ($expectedNamesBySelection as $selection => $expectedNames) {
            $component = Livewire::test(ParkingSpots::class, [
                'engineDisplacement' => $selection,
            ])->call('updateBounds', $this->mapBounds());

            foreach (array_keys($classes) as $name) {
                in_array($name, $expectedNames, true)
                    ? $component->assertSee($name)
                    : $component->assertDontSee($name);
            }

            $component->assertDispatched(
                'displayMarkers',
                function (string $event, array $params) use ($expectedNames): bool {
                    $names = collect($params['spots'])->pluck('name')->sort()->values()->all();
                    sort($expectedNames);

                    return $names === $expectedNames;
                },
            );
        }

        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('50cc対応')
            ->assertSee('125cc対応')
            ->assertSee('400cc対応')
            ->assertSee('大型対応')
            ->assertSee('区分未設定');
    }

    public function test_search_query_restores_the_selected_class_in_form_and_livewire(): void
    {
        $response = $this->get(route('search', [
            'keyword' => null,
            'lat' => 35.681167,
            'lon' => 139.767052,
            'engine_displacement' => EngineDisplacementClass::Over400cc->value,
        ]))->assertOk();

        $response
            ->assertSee('name="engine_displacement"', false)
            ->assertSee('400cc超');

        $this->assertMatchesRegularExpression(
            '/<option value="over_400cc"\s+selected>/',
            $response->getContent(),
        );

        $this->assertStringContainsString(
            '&quot;engineDisplacement&quot;:&quot;over_400cc&quot;',
            $response->getContent(),
        );
    }

    /**
     * @return array{User, Postalcode}
     */
    private function createUserAndPostalcode(): array
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
        $user = User::create([
            'user_id' => 'displacement-test-user',
            'name' => 'Displacement Test User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);

        return [$user, $postalcode];
    }

    private function createParkingSpot(
        User $user,
        Postalcode $postalcode,
        string $name,
        ?EngineDisplacementClass $class,
    ): ParkingSpot {
        return ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => $name,
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'max_displacement_class' => $class?->value,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);
    }

    private function formInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => '排気量区分テスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
            'max_displacement_class' => EngineDisplacementClass::UpTo50cc->value,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->rateInput()],
        ], $overrides);
    }

    private function rateInput(): array
    {
        return [
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'no_free_minutes' => '1',
            'max_rate' => 1200,
            'no_max_rate' => '0',
        ];
    }

    /**
     * @return array{south: float, north: float, west: float, east: float}
     */
    private function mapBounds(): array
    {
        return [
            'south' => 35.0,
            'north' => 36.0,
            'west' => 139.0,
            'east' => 140.0,
        ];
    }
}
