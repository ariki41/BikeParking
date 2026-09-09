<?php

namespace App\Services;

use App\Exceptions\YolpApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class YolpApiClient
{
    /**
     * @return array{lon: string, lat: string, address?: string}|null
     */
    public function search(string $keyword): ?array
    {
        return $this->request(
            (string) config('services.yolp.search_url'),
            [
                'query' => $keyword,
                'sort' => 'hybrid',
                'ac' => 'JP',
                'results' => 1,
                'detail' => 'simple',
                'output' => 'json',
            ],
        );
    }

    /**
     * @return array{lon: string, lat: string, address: string}|null
     */
    public function geocode(string $address): ?array
    {
        $location = $this->request(
            (string) config('services.yolp.geocode_url'),
            [
                'query' => $address,
                'sort' => 'score',
                'results' => 1,
                'output' => 'json',
            ],
        );

        if ($location === null || ! isset($location['address'])) {
            return null;
        }

        return $location;
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array{lon: string, lat: string, address?: string}|null
     */
    private function request(string $url, array $query): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.yolp.timeout_seconds'))
                ->retry(
                    (int) config('services.yolp.retry.times'),
                    (int) config('services.yolp.retry.sleep_milliseconds'),
                )
                ->get($url, [
                    'appid' => (string) config('services.yolp.client_id'),
                    ...$query,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new YolpApiException($this->connectionFailureCategory($exception), $exception);
        } catch (RequestException $exception) {
            throw new YolpApiException(YolpApiException::CATEGORY_RESPONSE, $exception);
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('Feature', $payload) || ! is_array($payload['Feature'])) {
            throw new YolpApiException(YolpApiException::CATEGORY_RESPONSE);
        }

        if ($payload['Feature'] === []) {
            return null;
        }

        $location = $this->normalizeFeature($payload['Feature'][0] ?? null);

        if ($location === null) {
            throw new YolpApiException(YolpApiException::CATEGORY_RESPONSE);
        }

        return $location;
    }

    private function connectionFailureCategory(ConnectionException $exception): string
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'curl error 28')
            ? YolpApiException::CATEGORY_TIMEOUT
            : YolpApiException::CATEGORY_CONNECTION;
    }

    /**
     * @return array{lon: string, lat: string, address?: string}|null
     */
    private function normalizeFeature(mixed $feature): ?array
    {
        if (! is_array($feature)) {
            return null;
        }

        $coordinates = data_get($feature, 'Geometry.Coordinates');

        if (! is_string($coordinates) || ! str_contains($coordinates, ',')) {
            return null;
        }

        // YOLPの座標文字列は、地図UIで混同しやすい「経度,緯度」の順で返される。
        [$longitude, $latitude] = array_map('trim', explode(',', $coordinates, 2));

        if ($longitude === '' || $latitude === '') {
            return null;
        }

        $location = [
            'lon' => $longitude,
            'lat' => $latitude,
        ];
        $address = data_get($feature, 'Property.Address');

        if (is_string($address) && $address !== '') {
            $location['address'] = $address;
        }

        return $location;
    }
}
