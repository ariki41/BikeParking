<?php

namespace App\Domain\ParkingSpots;

enum EngineDisplacementClass: string
{
    case UpTo50cc = 'up_to_50cc';
    case UpTo125cc = 'up_to_125cc';
    case UpTo400cc = 'up_to_400cc';
    case Over400cc = 'over_400cc';

    public function label(): string
    {
        return match ($this) {
            self::UpTo50cc => '原付（50cc以下）',
            self::UpTo125cc => '原付二種（125cc以下）',
            self::UpTo400cc => '中型（400cc以下）',
            self::Over400cc => '大型（400cc超を含む）',
        };
    }

    public function searchLabel(): string
    {
        return match ($this) {
            self::UpTo50cc => '50cc以下',
            self::UpTo125cc => '125cc以下',
            self::UpTo400cc => '400cc以下',
            self::Over400cc => '400cc超',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::UpTo50cc => 1,
            self::UpTo125cc => 2,
            self::UpTo400cc => 3,
            self::Over400cc => 4,
        };
    }

    /**
     * @return list<string>
     */
    public function supportedByValues(): array
    {
        return array_values(array_map(
            static fn (self $class): string => $class->value,
            array_filter(
                self::cases(),
                fn (self $class): bool => $class->rank() >= $this->rank(),
            ),
        ));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
