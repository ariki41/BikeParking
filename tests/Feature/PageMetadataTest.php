<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.name' => 'MotoLotz']);
    }

    public function test_home_search_and_static_pages_render_distinct_metadata(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>バイク駐輪場を探す | MotoLotz</title>', false)
            ->assertSee('<meta name="description" content="駅名や地名から、バイク駐輪場の料金、営業時間、場所を検索できます。">', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('href="'.asset('images/motolotz-favicon.png').'"', false);

        $this->get(route('search', ['keyword' => '東京駅']))
            ->assertOk()
            ->assertSee('<title>「東京駅」のバイク駐輪場を検索 | MotoLotz</title>', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="'.route('search').'">', false);

        foreach (['privacy' => 'プライバシーポリシー', 'terms' => '利用規約', 'contact' => 'お問い合わせ'] as $route => $title) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('<title>'.$title.' | MotoLotz</title>', false)
                ->assertSee('<link rel="canonical" href="'.route($route).'">', false);
        }
    }

    public function test_published_parking_spot_has_regional_share_metadata(): void
    {
        $parkingSpot = $this->createParkingSpot();
        $title = '東京千代田区のテスト駐輪場（東京都千代田区）のバイク駐輪場 | MotoLotz';
        $description = '東京都千代田区の東京千代田区のテスト駐輪場。料金、営業時間、場所を確認できます。';

        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('<title>'.$title.'</title>', false)
            ->assertSee('<meta name="description" content="'.$description.'">', false)
            ->assertSee('<meta property="og:title" content="'.$title.'">', false)
            ->assertSee('<meta property="og:description" content="'.$description.'">', false)
            ->assertSee('<meta property="og:url" content="'.route('parking_spot.show', $parkingSpot).'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/noimage.jpg').'">', false)
            ->assertDontSee('<meta name="robots"', false);
    }

    public function test_unpublished_parking_spot_is_excluded_from_indexing(): void
    {
        $parkingSpot = $this->createParkingSpot(['is_published' => false]);

        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="'.route('parking_spot.show', $parkingSpot).'">', false);
    }

    public function test_private_management_pages_have_titles_and_are_excluded_from_indexing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('parking_spot.create'))
            ->assertOk()
            ->assertSee('<title>駐輪場を登録 | MotoLotz</title>', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        foreach ([
            'profile.edit' => 'プロフィール',
            'profile.reviews' => '投稿したレビュー',
            'profile.images' => '投稿した画像',
            'profile.parking-spots' => '登録した駐輪場',
            'profile.edited-parking-spots' => '編集した駐輪場',
            'favorites.index' => 'お気に入り',
        ] as $route => $title) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('<title>'.$title.' | MotoLotz</title>', false)
                ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function createParkingSpot(array $attributes = []): ParkingSpot
    {
        $prefecture = Prefecture::factory()->create(['name' => '東京都']);
        $city = City::factory()->for($prefecture)->create(['name' => '千代田区']);
        $postalcode = Postalcode::factory()->for($city)->create();

        return ParkingSpot::factory()->for($postalcode)->create([
            'name' => '東京千代田区のテスト駐輪場',
            'address' => '東京都千代田区千代田1-1',
            ...$attributes,
        ]);
    }
}
