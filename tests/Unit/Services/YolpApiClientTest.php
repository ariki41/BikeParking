<?php

namespace Tests\Unit\Services;

use App\Exceptions\YolpApiException;
use App\Services\YolpApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YolpApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.yolp.search_url' => 'https://yolp.test/local-search',
            'services.yolp.geocode_url' => 'https://yolp.test/geocode',
            'services.yolp.client_id' => 'test-client-id',
            'services.yolp.timeout_seconds' => 5,
            'services.yolp.retry.times' => 3,
            'services.yolp.retry.sleep_milliseconds' => 0,
        ]);
        Http::preventStrayRequests();
    }

    public function test_search_returns_normalized_location(): void
    {
        Http::fake([
            'https://yolp.test/local-search*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.767052,35.681167'],
                ]],
            ]),
        ]);

        $location = app(YolpApiClient::class)->search('東京駅');

        $this->assertSame([
            'lon' => '139.767052',
            'lat' => '35.681167',
        ], $location);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://yolp.test/local-search?')
            && $request['appid'] === 'test-client-id'
            && $request['query'] === '東京駅'
            && $request['sort'] === 'hybrid'
            && $request['ac'] === 'JP'
            && $request['results'] === 1
            && $request['detail'] === 'simple'
            && $request['output'] === 'json');
    }

    public function test_geocode_returns_normalized_location_and_address(): void
    {
        Http::fake([
            'https://yolp.test/geocode*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都千代田区千代田1-1'],
                ]],
            ]),
        ]);

        $location = app(YolpApiClient::class)->geocode('東京都千代田区千代田1-1');

        $this->assertSame([
            'lon' => '139.753000',
            'lat' => '35.685000',
            'address' => '東京都千代田区千代田1-1',
        ], $location);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://yolp.test/geocode?')
            && $request['appid'] === 'test-client-id'
            && $request['query'] === '東京都千代田区千代田1-1'
            && $request['sort'] === 'score'
            && $request['results'] === 1
            && $request['output'] === 'json');
    }

    public function test_search_and_geocode_return_null_when_no_feature_exists(): void
    {
        Http::fake(['https://yolp.test/*' => Http::response(['Feature' => []])]);

        $client = app(YolpApiClient::class);

        $this->assertNull($client->search('存在しない駅'));
        $this->assertNull($client->geocode('存在しない住所'));
    }

    public function test_connection_failure_is_retried_and_wrapped(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::failedConnection('connection failed')]);

        try {
            app(YolpApiClient::class)->search('東京駅');
            $this->fail('YolpApiException was not thrown.');
        } catch (YolpApiException $exception) {
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }

        Http::assertSentCount(3);
    }
}
