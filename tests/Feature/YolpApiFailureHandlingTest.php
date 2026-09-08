<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\City;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YolpApiFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.yolp.retry.sleep_milliseconds', 0);
    }

    public function test_search_keeps_the_keyword_and_shows_a_retryable_error_when_yolp_is_unavailable(): void
    {
        Http::fake(['*' => Http::failedConnection('connection failed')]);

        $this->get(route('search', ['keyword' => '東京駅']))
            ->assertOk()
            ->assertSee('位置情報サービスに接続できません。時間をおいて、もう一度お試しください。')
            ->assertSee('value="東京駅"', false);
    }

    public function test_parking_spot_confirmation_keeps_input_and_shows_the_same_error_when_yolp_returns_an_invalid_response(): void
    {
        $postalcode = $this->createPostalcode();
        $user = User::create([
            'user_id' => 'yolp-failure-user',
            'name' => 'YOLP Failure User',
            'password' => Hash::make('password'),
            'prefecture_id' => $postalcode->city->prefecture_id,
        ]);
        $createUrl = route('parking_spot.create');
        Http::fake(['*' => Http::response(['unexpected' => []])]);

        $this->actingAs($user)->get($createUrl)->assertOk();

        $this->from($createUrl)
            ->post(route('parking_spot.confirm'), $this->formInput($postalcode))
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors([
                'address2' => '位置情報サービスに接続できません。時間をおいて、もう一度お試しください。',
            ]);

        $this->get($createUrl)
            ->assertOk()
            ->assertSee('value="障害時も保持される住所"', false);
    }

    private function createPostalcode(): Postalcode
    {
        $prefecture = Prefecture::create(['name' => '東京都', 'name_kana' => 'トウキョウト']);
        $city = City::create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);

        return Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ])->load('city');
    }

    private function formInput(Postalcode $postalcode): array
    {
        return [
            'name' => 'YOLP障害テスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '障害時も保持される住所',
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
                'max_rate' => 1200,
                'no_max_rate' => '0',
            ]],
        ];
    }
}
