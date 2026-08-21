<?php

namespace App\Domain\ParkingSpotRates;

final class RateConflictDetector
{
    /**
     * @param  array<int, RatePeriod>  $periods
     * @return list<array{left: int, right: int}>
     */
    public function detect(array $periods): array
    {
        $indexes = array_keys($periods);
        $conflicts = [];

        for ($left = 0; $left < count($indexes); $left++) {
            for ($right = $left + 1; $right < count($indexes); $right++) {
                $leftIndex = $indexes[$left];
                $rightIndex = $indexes[$right];

                if ($periods[$leftIndex]->overlaps($periods[$rightIndex])) {
                    $conflicts[] = ['left' => $leftIndex, 'right' => $rightIndex];
                }
            }
        }

        return $conflicts;
    }
}
