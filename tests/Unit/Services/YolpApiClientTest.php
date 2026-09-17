<?php

namespace Tests\Unit\Services;

use App\Exceptions\YolpApiException;
use App\Services\YolpApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
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
            'services.yolp.cache_ttl_seconds' => 300,
            'services.yolp.timeout_seconds' => 5,
            'services.yolp.retry.times' => 3,
            'services.yolp.retry.sleep_milliseconds' => 0,
        ]);
        Cache::flush();
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

    public function test_search_returns_null_when_yolp_feature_has_no_usable_coordinates(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response([
            'Feature' => [[
                'Geometry' => [],
            ]],
        ])]);

        $this->assertNull(app(YolpApiClient::class)->search('愛知県1'));
    }

    public function test_search_caches_successful_results_for_normalized_keywords(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response([
            'Feature' => [[
                'Geometry' => ['Coordinates' => '139.767052,35.681167'],
            ]],
        ])]);

        $client = app(YolpApiClient::class);

        $this->assertSame($client->search(' 東京 駅 '), $client->search('東京　駅'));
        Http::assertSentCount(1);
    }

    public function test_search_caches_no_result_responses(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response(['Feature' => []])]);

        $client = app(YolpApiClient::class);

        $this->assertNull($client->search('存在しない駅'));
        $this->assertNull($client->search('存在しない駅'));
        Http::assertSentCount(1);
    }

    public function test_search_and_geocode_cache_keys_do_not_collide(): void
    {
        Http::fake([
            'https://yolp.test/local-search*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.767052,35.681167'],
                ]],
            ]),
            'https://yolp.test/geocode*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都'],
                ]],
            ]),
        ]);

        $client = app(YolpApiClient::class);

        $this->assertNotNull($client->search('東京都'));
        $this->assertNotNull($client->geocode('東京都'));
        Http::assertSentCount(2);
    }

    public function test_expired_cache_entry_requests_yolp_again(): void
    {
        config()->set('services.yolp.cache_ttl_seconds', 60);
        Http::fake(['https://yolp.test/local-search*' => Http::response([
            'Feature' => [[
                'Geometry' => ['Coordinates' => '139.767052,35.681167'],
            ]],
        ])]);

        $client = app(YolpApiClient::class);
        $client->search('東京駅');

        $this->travel(61)->seconds();
        $client->search('東京駅');

        Http::assertSentCount(2);
    }

    public function test_yolp_failures_are_not_cached(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return $attempts <= 3
                ? Http::failedConnection('connection failed')
                : Http::response([
                    'Feature' => [[
                        'Geometry' => ['Coordinates' => '139.767052,35.681167'],
                    ]],
                ]);
        });

        try {
            app(YolpApiClient::class)->search('東京駅');
            $this->fail('YolpApiException was not thrown.');
        } catch (YolpApiException) {
            // API障害は既存の例外フローへ渡し、キャッシュ値として保存しない。
        }

        $this->assertNotNull(app(YolpApiClient::class)->search('東京駅'));
        $this->assertSame(4, $attempts);
    }

    public function test_cache_failure_falls_back_to_the_existing_yolp_request_flow(): void
    {
        Cache::shouldReceive('get')->once()->andThrow(new RuntimeException('cache unavailable'));
        Http::fake(['https://yolp.test/local-search*' => Http::response([
            'Feature' => [[
                'Geometry' => ['Coordinates' => '139.767052,35.681167'],
            ]],
        ])]);

        $this->assertSame([
            'lon' => '139.767052',
            'lat' => '35.681167',
        ], app(YolpApiClient::class)->search('東京駅'));
        Http::assertSentCount(1);
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

    public function test_invalid_response_is_wrapped_as_a_response_failure(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response(['unexpected' => []])]);

        try {
            app(YolpApiClient::class)->search('東京駅');
            $this->fail('YolpApiException was not thrown.');
        } catch (YolpApiException $exception) {
            $this->assertSame(YolpApiException::CATEGORY_RESPONSE, $exception->category());
        }
    }

    public function test_client_error_is_wrapped_as_a_response_failure_without_caching_it(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::response(['Error' => []], 400)]);

        try {
            app(YolpApiClient::class)->search('愛知県1');
            $this->fail('YolpApiException was not thrown.');
        } catch (YolpApiException $exception) {
            $this->assertSame(YolpApiException::CATEGORY_RESPONSE, $exception->category());
        }
    }

    public function test_timeout_is_classified_separately_from_other_connection_failures(): void
    {
        Http::fake(['https://yolp.test/local-search*' => Http::failedConnection('cURL error 28: Operation timed out')]);

        try {
            app(YolpApiClient::class)->search('東京駅');
            $this->fail('YolpApiException was not thrown.');
        } catch (YolpApiException $exception) {
            $this->assertSame(YolpApiException::CATEGORY_TIMEOUT, $exception->category());
        }
    }
}
