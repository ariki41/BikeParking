<?php

namespace Tests\Unit\Services;

use App\Services\SearchService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.yolp.search_url' => 'https://yolp.test/local-search',
            'services.yolp.client_id' => 'test-client-id',
            'services.yolp.retry.sleep_milliseconds' => 0,
        ]);
        Http::preventStrayRequests();
    }

    public function test_search_uses_yolp_client_location(): void
    {
        Http::fake([
            'https://yolp.test/local-search*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.767052,35.681167'],
                ]],
            ]),
        ]);
        $request = HttpRequest::create('/search', 'GET', ['keyword' => '東京駅']);

        $location = app(SearchService::class)->getYolpLocation($request);

        $this->assertSame([
            'lon' => '139.767052',
            'lat' => '35.681167',
        ], $location);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://yolp.test/local-search?'));
    }

    public function test_search_falls_back_to_requested_location_when_no_result_exists(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response(['Feature' => []])]);
        $request = HttpRequest::create('/search', 'GET', [
            'keyword' => '存在しない駅',
            'lon' => '135.000000',
            'lat' => '34.000000',
        ]);

        $location = app(SearchService::class)->getYolpLocation($request);

        $this->assertSame([
            'lon' => '135.000000',
            'lat' => '34.000000',
        ], $location);
        $this->assertSame('検索結果が見つかりませんでした。', session('error'));
    }
}
