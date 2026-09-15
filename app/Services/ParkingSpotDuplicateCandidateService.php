<?php

namespace App\Services;

use App\Models\ParkingSpot;
use Illuminate\Support\Collection;

class ParkingSpotDuplicateCandidateService
{
    /** @return Collection<int, ParkingSpot> */
    public function find(string $name, string $address, float $latitude, float $longitude): Collection
    {
        $distanceMeters = (float) config('parking_spot.duplicate_candidate_distance_meters');
        $latitudeDelta = $distanceMeters / 111_320;
        $longitudeDelta = $distanceMeters / (111_320 * max(cos(deg2rad($latitude)), 0.01));
        $normalizedName = $this->normalize($name);
        $normalizedAddress = $this->normalize($address);

        return ParkingSpot::published()
            ->where(function ($query) use ($address, $latitude, $longitude, $latitudeDelta, $longitudeDelta): void {
                $query->where('address', $address)
                    ->orWhere(function ($query) use ($latitude, $longitude, $latitudeDelta, $longitudeDelta): void {
                        $query->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
                            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta]);
                    });
            })
            ->get()
            ->filter(function (ParkingSpot $parkingSpot) use ($normalizedName, $normalizedAddress, $latitude, $longitude, $distanceMeters): bool {
                if ($this->normalize($parkingSpot->address) === $normalizedAddress) {
                    return true;
                }

                return $this->namesAreSimilar($normalizedName, $this->normalize($parkingSpot->name))
                    && $this->distanceInMeters($latitude, $longitude, $parkingSpot->latitude, $parkingSpot->longitude) <= $distanceMeters;
            })
            ->sortBy(fn (ParkingSpot $parkingSpot): float => $this->distanceInMeters($latitude, $longitude, $parkingSpot->latitude, $parkingSpot->longitude))
            ->values();
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[\\s\\p{P}]/u', '', mb_strtolower(mb_convert_kana($value, 'asKV'))) ?? '';
    }

    private function namesAreSimilar(string $first, string $second): bool
    {
        return $first !== '' && $second !== '' && (str_contains($first, $second) || str_contains($second, $first));
    }

    private function distanceInMeters(float $latitude, float $longitude, float $otherLatitude, float $otherLongitude): float
    {
        $latitudeDelta = deg2rad($otherLatitude - $latitude);
        $longitudeDelta = deg2rad($otherLongitude - $longitude);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($otherLatitude)) * sin($longitudeDelta / 2) ** 2;

        return 6_371_000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
