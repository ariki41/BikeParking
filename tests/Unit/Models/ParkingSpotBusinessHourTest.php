<?php

namespace Tests\Unit\Models;

use App\Models\ParkingSpotBusinessHour;
use PHPUnit\Framework\TestCase;

class ParkingSpotBusinessHourTest extends TestCase
{
    public function test_it_formats_a_timed_closure(): void
    {
        $businessHour = new ParkingSpotBusinessHour([
            'is_closed' => true,
            'opening_time' => '12:00:00',
            'closing_time' => '13:30:00',
        ]);

        $this->assertSame('12:00 ～ 13:30 休業', $businessHour->formattedHours());
    }

    public function test_it_formats_an_all_day_closure(): void
    {
        $businessHour = new ParkingSpotBusinessHour([
            'is_closed' => true,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        $this->assertSame('終日休業', $businessHour->formattedHours());
    }
}
