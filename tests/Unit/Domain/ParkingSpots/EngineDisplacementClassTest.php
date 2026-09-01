<?php

namespace Tests\Unit\Domain\ParkingSpots;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use PHPUnit\Framework\TestCase;

class EngineDisplacementClassTest extends TestCase
{
    public function test_it_defines_labels_order_and_supported_parking_values(): void
    {
        $this->assertSame(
            ['up_to_50cc', 'up_to_125cc', 'up_to_400cc', 'over_400cc'],
            EngineDisplacementClass::values(),
        );
        $this->assertSame('原付（50cc以下）', EngineDisplacementClass::UpTo50cc->label());
        $this->assertSame('大型（400cc超を含む）', EngineDisplacementClass::Over400cc->label());
        $this->assertSame(
            ['up_to_125cc', 'up_to_400cc', 'over_400cc'],
            EngineDisplacementClass::UpTo125cc->supportedByValues(),
        );
    }
}
