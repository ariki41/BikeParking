<?php

namespace App\Domain\ParkingSpotRates;

enum RateDayType: string
{
    case AllDays = '全日';
    case Weekdays = '平日';
    case Holidays = '土日祝';
    case Daytime = '昼間';
    case Nighttime = '夜間';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function overlaps(self $other): bool
    {
        return array_intersect($this->dayScopes(), $other->dayScopes()) !== [];
    }

    /**
     * @return list<string>
     */
    private function dayScopes(): array
    {
        return match ($this) {
            self::Weekdays => ['weekday'],
            self::Holidays => ['holiday'],
            self::AllDays, self::Daytime, self::Nighttime => ['weekday', 'holiday'],
        };
    }
}
