<?php

namespace App\Domain\ParkingSpotRates;

final readonly class RatePeriod
{
    private function __construct(
        private RateDayType $dayType,
        private RateTimeRange $timeRange,
    ) {}

    public static function fromValues(string $dayType, string $startTime, string $endTime): self
    {
        return new self(
            RateDayType::from($dayType),
            RateTimeRange::fromTimes($startTime, $endTime),
        );
    }

    public function overlaps(self $other): bool
    {
        return $this->dayType->overlaps($other->dayType)
            && $this->timeRange->overlaps($other->timeRange);
    }
}
