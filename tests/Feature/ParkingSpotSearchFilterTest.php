<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ParkingSpotSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Postalcode $postalcode;

    protected function setUp(): void
    {
        parent::setUp();

        $prefecture = Prefecture::create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $city = City::create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);
        $this->postalcode = Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
        $this->user = User::create([
            'user_id' => 'search-filter-user',
            'name' => 'Search Filter User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
    }

    public function test_capacity_filter_accepts_multiple_categories_as_an_or_condition(): void
    {
        foreach (config('categories.parking_spot_capacity') as $capacity => $label) {
            $this->createParkingSpot("収容区分{$capacity}", ['capacity' => $capacity]);
        }

        $component = Livewire::test(ParkingSpots::class)
            ->set('capacityDraft', ['1', '3'])
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('収容区分1')
            ->assertDontSee('収容区分2')
            ->assertSee('収容区分3')
            ->assertDontSee('収容区分4');

        $this->assertSame([1, 3], $component->get('filters')['capacity']);
        $this->assertSame('1,3', $component->get('capacityQuery'));
        $this->assertMarkerNames($component, ['収容区分1', '収容区分3']);
    }

    public function test_twenty_four_hour_filter_requires_both_times_to_be_midnight(): void
    {
        $this->createParkingSpot('終日営業', [
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);
        $this->createParkingSpot('開始だけ深夜', [
            'opening_time' => '00:00:00',
            'closing_time' => '20:00:00',
        ]);
        $this->createParkingSpot('終了だけ深夜', [
            'opening_time' => '06:00:00',
            'closing_time' => '00:00:00',
        ]);

        Livewire::test(ParkingSpots::class)
            ->set('open24HoursDraft', true)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('終日営業')
            ->assertDontSee('開始だけ深夜')
            ->assertDontSee('終了だけ深夜');
    }

    public function test_free_time_and_max_rate_boundaries_are_filtered_as_specified(): void
    {
        $freeBoundary = $this->createParkingSpot('無料0分');
        $this->createRate($freeBoundary, ['free_minutes' => 0, 'max_rate' => 1000]);

        $free = $this->createParkingSpot('無料1分');
        $this->createRate($free, ['free_minutes' => 1, 'max_rate' => 1000]);

        $overLimit = $this->createParkingSpot('上限超過');
        $this->createRate($overLimit, ['free_minutes' => 1, 'max_rate' => 1001]);

        $withoutMaximum = $this->createParkingSpot('最大料金なし');
        $this->createRate($withoutMaximum, ['free_minutes' => 1, 'max_rate' => null]);

        Livewire::test(ParkingSpots::class)
            ->set('hasFreeTimeDraft', true)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertDontSee('無料0分')
            ->assertSee('無料1分')
            ->assertSee('上限超過')
            ->assertSee('最大料金なし');

        Livewire::test(ParkingSpots::class)
            ->set('maxRateDraft', 1000)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('無料0分')
            ->assertSee('無料1分')
            ->assertDontSee('上限超過')
            ->assertDontSee('最大料金なし');

        Livewire::test(ParkingSpots::class)
            ->set('hasFreeTimeDraft', true)
            ->set('maxRateDraft', 1000)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertDontSee('無料0分')
            ->assertSee('無料1分')
            ->assertDontSee('上限超過')
            ->assertDontSee('最大料金なし');
    }

    public function test_rate_filters_must_match_the_same_rate_row(): void
    {
        $splitRates = $this->createParkingSpot('別料金帯で一致');
        $this->createRate($splitRates, ['free_minutes' => 30, 'max_rate' => null]);
        $this->createRate($splitRates, ['free_minutes' => 0, 'max_rate' => 800, 'day_type' => '平日']);

        $sameRate = $this->createParkingSpot('同一料金帯で一致');
        $this->createRate($sameRate, ['free_minutes' => 30, 'max_rate' => 800]);

        Livewire::test(ParkingSpots::class)
            ->set('hasFreeTimeDraft', true)
            ->set('maxRateDraft', 1000)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertDontSee('別料金帯で一致')
            ->assertSee('同一料金帯で一致');
    }

    public function test_different_filter_types_are_combined_as_and_conditions(): void
    {
        $match = $this->createParkingSpot('全条件一致', ['capacity' => 2]);
        $this->createRate($match, ['free_minutes' => 15, 'max_rate' => 900]);

        $wrongCapacity = $this->createParkingSpot('収容台数不一致', ['capacity' => 1]);
        $this->createRate($wrongCapacity, ['free_minutes' => 15, 'max_rate' => 900]);

        $notAlwaysOpen = $this->createParkingSpot('営業時間不一致', [
            'capacity' => 2,
            'opening_time' => '06:00:00',
            'closing_time' => '23:00:00',
        ]);
        $this->createRate($notAlwaysOpen, ['free_minutes' => 15, 'max_rate' => 900]);

        Livewire::test(ParkingSpots::class)
            ->set('capacityDraft', ['2'])
            ->set('open24HoursDraft', true)
            ->set('hasFreeTimeDraft', true)
            ->set('maxRateDraft', 1000)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('全条件一致')
            ->assertDontSee('収容台数不一致')
            ->assertDontSee('営業時間不一致');
    }

    public function test_filters_are_kept_when_map_bounds_change_and_markers_match_the_list(): void
    {
        $this->createParkingSpot('西側の対象', ['capacity' => 1, 'longitude' => 139.25]);
        $this->createParkingSpot('東側の対象', ['capacity' => 1, 'longitude' => 140.25]);
        $this->createParkingSpot('東側の対象外', ['capacity' => 2, 'longitude' => 140.25]);

        $component = Livewire::test(ParkingSpots::class)
            ->set('capacityDraft', ['1'])
            ->call('applyFilters')
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 139.0,
                'east' => 140.0,
            ])
            ->assertSee('西側の対象')
            ->assertDontSee('東側の対象')
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 140.0,
                'east' => 141.0,
            ])
            ->assertDontSee('西側の対象')
            ->assertSee('東側の対象')
            ->assertDontSee('東側の対象外');

        $this->assertSame([1], $component->get('filters')['capacity']);
        $this->assertMarkerNames($component, ['東側の対象']);
    }

    public function test_query_string_restores_filters_and_history_changes_refresh_drafts_and_results(): void
    {
        $small = $this->createParkingSpot('小規模', ['capacity' => 1]);
        $this->createRate($small, ['free_minutes' => 1, 'max_rate' => 900]);
        $unknown = $this->createParkingSpot('不明規模', ['capacity' => 4]);
        $this->createRate($unknown, ['free_minutes' => 1, 'max_rate' => 900]);

        $component = Livewire::withQueryParams([
            'capacity' => '2,1',
            'open_24_hours' => '1',
            'has_free_time' => '1',
            'max_rate' => '1000',
        ])->test(ParkingSpots::class);

        $component
            ->assertSet('filters.capacity', [1, 2])
            ->assertSet('capacityQuery', '1,2')
            ->assertSet('open24HoursQuery', '1')
            ->assertSet('hasFreeTimeQuery', '1')
            ->assertSet('maxRateQuery', '1000')
            ->assertSet('capacityDraft', [1, 2])
            ->assertSet('open24HoursDraft', true)
            ->assertSet('hasFreeTimeDraft', true)
            ->assertSet('maxRateDraft', '1000')
            ->assertSee('5件適用中')
            ->assertSee('最大料金: 1,000円以下');

        $effects = html_entity_decode($component->html());
        $this->assertStringContainsString('"use":"push"', $effects);
        $this->assertStringContainsString('"as":"capacity"', $effects);
        $this->assertStringNotContainsString('"as":"filters"', $effects);

        $component
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('小規模')
            ->assertDontSee('不明規模')
            ->set([
                'capacityQuery' => '4',
                'open24HoursQuery' => '',
                'hasFreeTimeQuery' => '',
                'maxRateQuery' => '',
            ])
            ->assertSet('filters', ['capacity' => [4]])
            ->assertSet('capacityDraft', [4])
            ->assertSet('open24HoursDraft', false)
            ->assertSet('hasFreeTimeDraft', false)
            ->assertSet('maxRateDraft', null)
            ->assertDontSee('小規模')
            ->assertSee('不明規模');
    }

    public function test_keyword_form_preserves_applied_filters_and_search_ui_is_available(): void
    {
        $url = route('search', [
            'lat' => 35.681167,
            'lon' => 139.767052,
            'capacity' => '1,3',
            'open_24_hours' => 1,
            'has_free_time' => 1,
            'max_rate' => 1200,
            'engine_displacement' => 'over_400cc,up_to_125cc',
            'zoom' => 17,
        ]);

        $this->assertStringNotContainsString('[', $url);
        $this->assertStringNotContainsString('%5B', $url);

        $response = $this->get($url)->assertOk();

        $response
            ->assertSee('絞り込み')
            ->assertSee('この条件で絞り込む')
            ->assertSee('条件をクリア')
            ->assertSee('その他の条件（複数選択可）')
            ->assertSee('x-bind:aria-expanded="open.toString()"', false)
            ->assertSee('name="capacity"', false)
            ->assertSee('value="1,3"', false)
            ->assertSee('name="open_24_hours"', false)
            ->assertSee('name="has_free_time"', false)
            ->assertSee('name="max_rate"', false)
            ->assertSee('name="engine_displacement" type="hidden" value="up_to_125cc,over_400cc"', false)
            ->assertSee('name="zoom" type="hidden" value="17"', false)
            ->assertDontSee('name="filters[', false)
            ->assertSee('7件適用中')
            ->assertSee('排気量: 125cc以下')
            ->assertSee('排気量: 400cc超');
    }

    public function test_map_view_is_kept_in_the_flat_query_string_and_get_form(): void
    {
        $component = Livewire::withQueryParams([
            'lat' => '35.681167',
            'lon' => '139.767052',
            'zoom' => '17',
        ])
            ->test(ParkingSpots::class)
            ->assertSet('latitude', 35.681167)
            ->assertSet('longitude', 139.767052)
            ->assertSet('zoom', 17)
            ->assertSee('name="zoom" type="hidden" value="17"', false);

        $effects = html_entity_decode($component->html());

        $this->assertStringContainsString('"as":"lat"', $effects);
        $this->assertStringContainsString('"as":"lon"', $effects);
        $this->assertStringContainsString('"as":"zoom"', $effects);
        $this->assertStringContainsString('"use":"replace"', $effects);
        $this->assertStringContainsString('"alwaysShow":true', $effects);
        $this->assertStringNotContainsString('"as":"lat[', $effects);
        $this->assertStringNotContainsString('"as":"lon[', $effects);
        $this->assertStringNotContainsString('"as":"zoom[', $effects);

        $component
            ->call('updateBounds', $this->mapBounds(), 18, [
                'latitude' => 35.7001234,
                'longitude' => 139.8005678,
            ])
            ->assertSet('latitude', 35.700123)
            ->assertSet('longitude', 139.800568)
            ->assertSet('zoom', 18)
            ->assertSee('name="lat" type="hidden" value="35.700123"', false)
            ->assertSee('name="lon" type="hidden" value="139.800568"', false)
            ->assertSee('name="zoom" type="hidden" value="18"', false)
            ->call('updateBounds', $this->mapBounds(), 18, [
                'latitude' => 91,
                'longitude' => 181,
            ])
            ->assertSet('latitude', 35.700123)
            ->assertSet('longitude', 139.800568);
    }

    public function test_invalid_max_rate_is_shown_near_the_input_without_changing_applied_results(): void
    {
        $this->createParkingSpot('表示を維持する駐輪場');

        $component = Livewire::test(ParkingSpots::class)
            ->call('updateBounds', $this->mapBounds())
            ->set('maxRateDraft', 0)
            ->call('applyFilters')
            ->assertHasErrors(['maxRateDraft' => 'min'])
            ->assertSee('最大料金上限は1円以上で入力してください。')
            ->assertSee('id="max-rate-filter-error"', false)
            ->assertSee('表示を維持する駐輪場');

        $this->assertSame([], $component->get('filters'));

        Livewire::withQueryParams(['max_rate' => 'invalid'])
            ->test(ParkingSpots::class)
            ->assertHasErrors('maxRateDraft')
            ->assertSee('最大料金上限は1円以上の整数で入力してください。');
    }

    public function test_clear_removes_all_filters_and_restores_unfiltered_results(): void
    {
        $this->createParkingSpot('小規模', ['capacity' => 1]);
        $this->createParkingSpot('大規模', ['capacity' => 3]);

        $component = Livewire::test(ParkingSpots::class)
            ->assertSee('type="reset" wire:click="clearFilters"', false)
            ->assertSet('filterFormVersion', 0)
            ->set('capacityDraft', ['1'])
            ->set('open24HoursDraft', true)
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertSee('小規模')
            ->assertDontSee('大規模')
            ->call('clearFilters')
            ->assertSet('filters', [])
            ->assertSet('capacityDraft', [])
            ->assertSet('open24HoursDraft', false)
            ->assertSet('hasFreeTimeDraft', false)
            ->assertSet('maxRateDraft', null)
            ->assertSet('capacityQuery', '')
            ->assertSet('open24HoursQuery', '')
            ->assertSet('hasFreeTimeQuery', '')
            ->assertSet('maxRateQuery', '')
            ->assertSet('filterFormVersion', 1)
            ->assertSee('wire:key="parking-spot-filters-1"', false)
            ->assertSee('小規模')
            ->assertSee('大規模')
            ->assertDontSee('件適用中')
            ->assertDontSee('name="capacity"', false);

        $this->assertMarkerNames($component, ['大規模', '小規模']);
    }

    public function test_empty_result_shows_the_empty_state_and_clears_markers(): void
    {
        $this->createParkingSpot('小規模', ['capacity' => 1]);

        $component = Livewire::test(ParkingSpots::class)
            ->set('capacityDraft', ['3'])
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds())
            ->assertDontSee('小規模')
            ->assertSee('条件に一致する駐輪場がありません。条件または地図範囲を変更してください。');

        $this->assertMarkerNames($component, []);
    }

    public function test_fifty_item_limit_is_applied_after_filtering(): void
    {
        foreach (range(1, 51) as $number) {
            $this->createParkingSpot("対象{$number}", ['capacity' => 1]);
        }

        foreach (range(1, 10) as $number) {
            $this->createParkingSpot("対象外{$number}", ['capacity' => 2]);
        }

        $component = Livewire::test(ParkingSpots::class)
            ->set('capacityDraft', ['1'])
            ->call('applyFilters')
            ->call('updateBounds', $this->mapBounds());

        $this->assertCount(50, $component->get('spots'));
        $this->assertSame(
            [],
            collect($component->get('spots'))->where('capacity', 2)->values()->all(),
        );
    }

    private function createParkingSpot(string $name, array $overrides = []): ParkingSpot
    {
        return ParkingSpot::forceCreate(array_replace([
            'user_id' => $this->user->id,
            'name' => $name,
            'postalcode_id' => $this->postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.75,
            'latitude' => 35.68,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ], $overrides));
    }

    private function createRate(ParkingSpot $parkingSpot, array $overrides = []): ParkingSpotRates
    {
        return ParkingSpotRates::create(array_replace([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '全日',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1000,
        ], $overrides));
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

    private function assertMarkerNames($component, array $expectedNames): void
    {
        sort($expectedNames);

        $component->assertDispatched(
            'displayMarkers',
            function (string $event, array $params) use ($expectedNames): bool {
                $actualNames = collect($params['spots'])->pluck('name')->sort()->values()->all();

                return $actualNames === $expectedNames;
            },
        );
    }
}
