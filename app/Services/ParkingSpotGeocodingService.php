<?php

namespace App\Services;

class ParkingSpotGeocodingService
{
    public function __construct(private readonly YolpApiClient $client) {}

    /**
     * @return array{lon: string, lat: string, address: string}|null
     */
    public function geocode(string $address): ?array
    {
        return $this->client->geocode($address);
    }
}
