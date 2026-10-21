<?php

namespace Tests\Unit;

use App\Support\WeatherCode;
use PHPUnit\Framework\TestCase;

class WeatherCodeTest extends TestCase
{
    public function test_it_labels_and_groups_wmo_codes(): void
    {
        $this->assertSame('Clear sky', WeatherCode::label(0));
        $this->assertSame('Partly cloudy', WeatherCode::label(2));
        $this->assertSame('Rain', WeatherCode::label(63));
        $this->assertSame('Snow', WeatherCode::label(73));
        $this->assertSame('Thunderstorm', WeatherCode::label(95));
        $this->assertSame('Unknown conditions', WeatherCode::label(999));

        $this->assertSame('clear', WeatherCode::group(0));
        $this->assertSame('cloud', WeatherCode::group(3));
        $this->assertSame('fog', WeatherCode::group(45));
        $this->assertSame('rain', WeatherCode::group(61));
        $this->assertSame('snow', WeatherCode::group(85));
        $this->assertSame('storm', WeatherCode::group(95));
        $this->assertSame('unknown', WeatherCode::group(-1));
    }
}
