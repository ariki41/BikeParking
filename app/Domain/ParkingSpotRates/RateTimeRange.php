<?php

namespace App\Domain\ParkingSpotRates;

use InvalidArgumentException;

final readonly class RateTimeRange
{
    private const MINUTES_PER_DAY = 1440;

    private function __construct(
        private int $start,
        private int $end,
    ) {}

    public static function fromTimes(string $startTime, string $endTime): self
    {
        return new self(
            self::timeToMinutes($startTime),
            self::timeToMinutes($endTime),
        );
    }

    public function overlaps(self $other): bool
    {
        foreach ($this->segments() as [$start, $end]) {
            foreach ($other->segments() as [$otherStart, $otherEnd]) {
                if (max($start, $otherStart) < min($end, $otherEnd)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 同一時刻は終日、それ以外の日付またぎは日末と日始めで分割する。
     *
     * @return list<array{int, int}>
     */
    private function segments(): array
    {
        if ($this->start === $this->end) {
            return [[0, self::MINUTES_PER_DAY]];
        }

        if ($this->start < $this->end) {
            return [[$this->start, $this->end]];
        }

        return [
            [$this->start, self::MINUTES_PER_DAY],
            [0, $this->end],
        ];
    }

    private static function timeToMinutes(string $time): int
    {
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            throw new InvalidArgumentException("Invalid time: {$time}");
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
