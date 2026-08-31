<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParkingSpotFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_renders_the_shared_fields_with_create_defaults(): void
    {
        [, $user] = $this->createParkingSpot();

        $this->actingAs($user)
            ->get(route('parking_spot.create'))
            ->assertOk()
            ->assertSee('駐車場の新規登録')
            ->assertSee('action="'.route('parking_spot.confirm').'"', false)
            ->assertSee('id="parking-spot-basic-heading"', false)
            ->assertSee('id="parking-spot-hours-heading"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="postalcode"', false)
            ->assertSee('name="address1"', false)
            ->assertSee('name="address2"', false)
            ->assertSee('name="capacity"', false)
            ->assertSee('name="opening_time"', false)
            ->assertSee('name="closing_time"', false)
            ->assertSee('name="images[]"', false)
            ->assertSee('name="rates[0][rate]"', false)
            ->assertSee('data-parking-spot-rates data-max-rates="4"', false)
            ->assertSee('data-rate-template', false)
            ->assertSee('value="00:00"', false)
            ->assertDontSee("document.addEventListener('DOMContentLoaded'", false)
            ->assertDontSee('name="id"', false);
    }

    public function test_create_form_restores_flushed_input_and_associates_validation_errors(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        $createUrl = route('parking_spot.create');

        $this->actingAs($user)->get($createUrl)->assertOk();

        $this->from($createUrl)
            ->post(route('parking_spot.confirm'), $this->formInput($postalcode, [
                'name' => '',
                'address2' => '9-8-7 セッション入力',
                'capacity' => 2,
                'opening_time' => '08:15',
                'closing_time' => '21:45',
                'rates' => [$this->rateInput(['rate' => 250])],
            ]))
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors(['name']);

        $this->get($createUrl)
            ->assertOk()
            ->assertSee('駐車場名は必須です。')
            ->assertSee('aria-describedby="name-error"', false)
            ->assertSee('value="9-8-7 セッション入力"', false)
            ->assertSee('<option value="2" selected>', false)
            ->assertSee('value="08:15"', false)
            ->assertSee('value="21:45"', false)
            ->assertSee('value="250"', false);
    }

    public function test_edit_form_uses_existing_values_and_restores_flushed_input_over_them(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        $parkingSpot->images()->create([
            'path' => 'parking-spots/existing.webp',
            'position' => 0,
        ]);
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            ...$this->rateInput(['rate' => 100]),
        ]);
        $editUrl = route('parking_spot.edit', $parkingSpot);

        $this->actingAs($user)
            ->get($editUrl)
            ->assertOk()
            ->assertSee('駐車場の編集')
            ->assertSee('name="id" type="hidden" value="'.$parkingSpot->id.'"', false)
            ->assertSee('value="既存の駐車場"', false)
            ->assertSee('value="1000001"', false)
            ->assertSee('value="東京都千代田区千代田"', false)
            ->assertSee('value="1-1"', false)
            ->assertSee('/storage/parking-spots/existing.webp')
            ->assertSee('value="100"', false)
            ->assertSee('data-parking-spot-rates data-max-rates="4"', false)
            ->assertSee('data-rate-template', false)
            ->assertDontSee("document.addEventListener('DOMContentLoaded'", false);

        $this->from($editUrl)
            ->post(route('parking_spot.confirm'), $this->formInput($postalcode, [
                'id' => $parkingSpot->id,
                'name' => 'セッションから復元した駐車場',
                'address2' => '2-3-4',
                'capacity' => 3,
                'opening_time' => '06:30',
                'closing_time' => 'invalid',
                'image_paths' => ['temp/parking-spots/restored.webp'],
                'rates' => [$this->rateInput(['rate' => 300])],
            ]))
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors(['closing_time']);

        $this->get($editUrl)
            ->assertOk()
            ->assertSee('セッションから復元した駐車場')
            ->assertDontSee('value="既存の駐車場"', false)
            ->assertSee('value="2-3-4"', false)
            ->assertSee('<option value="3" selected>', false)
            ->assertSee('value="06:30"', false)
            ->assertSee('/storage/temp/parking-spots/restored.webp')
            ->assertDontSee('/storage/parking-spots/existing.webp')
            ->assertSee('value="300"', false)
            ->assertSee('閉場時間の形式が正しくありません。例: 22:00')
            ->assertSee('aria-describedby="closing-time-error"', false);
    }

    private function createParkingSpot(): array
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
            'user_id' => 'parking-form-user',
            'name' => 'Parking Form User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '既存の駐車場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user, $postalcode];
    }

    private function formInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => 'フォームテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->rateInput()],
        ], $overrides);
    }

    private function rateInput(array $overrides = []): array
    {
        return array_replace([
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'no_free_minutes' => '1',
            'max_rate' => 1200,
            'no_max_rate' => '0',
        ], $overrides);
    }
}
