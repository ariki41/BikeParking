<?php

namespace App\Domain\ParkingSpotRates;

use App\Models\ParkingSpotRates;

/**
 * The canonical, view-facing representation of a parking rate.
 *
 * It accepts both persisted models and the validated arrays shown on the
 * confirmation page, so a rate is described identically before and after it
 * is saved.
 */
final readonly class RateDisplay
{
    private function __construct(
        public string $dayType,
        public string $timeRangeLabel,
        public string $baseRateLabel,
        public string $maxRateLabel,
        public string $rateLabel,
    ) {}

    public static function fromModel(ParkingSpotRates $rate): self
    {
        return self::fromValues(
            $rate->day_type,
            $rate->start_time,
            $rate->end_time,
            $rate->unit_minutes,
            $rate->rate,
            $rate->free_minutes,
            $rate->max_rate,
        );
    }

    /**
     * @param  array{day_type?: mixed, start_time?: mixed, end_time?: mixed, unit_minutes?: mixed, rate?: mixed, free_minutes?: mixed, max_rate?: mixed}  $rate
     */
    public static function fromArray(array $rate): self
    {
        return self::fromValues(
            (string) ($rate['day_type'] ?? ''),
            self::nullableString($rate['start_time'] ?? null),
            self::nullableString($rate['end_time'] ?? null),
            (int) ($rate['unit_minutes'] ?? 0),
            (int) ($rate['rate'] ?? 0),
            (int) ($rate['free_minutes'] ?? 0),
            self::nullableInteger($rate['max_rate'] ?? null),
        );
    }

    public static function formatTimeRange(?string $startTime, ?string $endTime): string
    {
        // 料金設定では00:00から00:00を同時刻ではなく終日料金として扱う。
        if (self::isFullDayRange($startTime, $endTime)) {
            return '00:00 ～ 24:00';
        }

        $startLabel = self::formatTimeLabel($startTime);
        $endLabel = self::formatTimeLabel($endTime, self::isOvernight($startTime, $endTime));

        return "{$startLabel} ～ {$endLabel}";
    }

    private static function fromValues(
        string $dayType,
        ?string $startTime,
        ?string $endTime,
        int $unitMinutes,
        int $rate,
        int $freeMinutes,
        ?int $maxRate,
    ): self {
        if ($rate === 0) {
            return new self(
                $dayType,
                self::formatTimeRange($startTime, $endTime),
                '無料',
                self::maxRateLabel($maxRate),
                '無料',
            );
        }

        $baseRateLabel = self::formatMinutes($unitMinutes).' '.number_format($rate).'円';

        if ($freeMinutes > 0) {
            $baseRateLabel = '最初の'.self::formatMinutes($freeMinutes).'無料 / 以降'.$baseRateLabel;
        }

        $maxRateLabel = self::maxRateLabel($maxRate);

        return new self(
            $dayType,
            self::formatTimeRange($startTime, $endTime),
            $baseRateLabel,
            $maxRateLabel,
            $baseRateLabel.($maxRate === null ? ' / 最大料金なし' : ' / 最大 '.number_format($maxRate).'円'),
        );
    }

    private static function maxRateLabel(?int $maxRate): string
    {
        return $maxRate === null ? '最大料金なし' : number_format($maxRate).'円';
    }

    private static function isOvernight(?string $startTime, ?string $endTime): bool
    {
        return $startTime !== null && $endTime !== null
            && self::normalizeTime($startTime) > self::normalizeTime($endTime);
    }

    private static function isFullDayRange(?string $startTime, ?string $endTime): bool
    {
        return $startTime !== null && $endTime !== null
            && self::normalizeTime($startTime) === '00:00'
            && self::normalizeTime($endTime) === '00:00';
    }

    private static function formatTimeLabel(?string $time, bool $isNextDay = false): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $label = self::normalizeTime($time);

        if (! $isNextDay && $label === '00:00') {
            return '24:00';
        }

        return $isNextDay ? "翌{$label}" : $label;
    }

    private static function normalizeTime(string $time): string
    {
        return date('H:i', strtotime($time));
    }

    private static function formatMinutes(int $minutes): string
    {
        if ($minutes >= 60 && $minutes % 60 === 0) {
            return ($minutes / 60).'時間';
        }

        return "{$minutes}分";
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private static function nullableInteger(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
