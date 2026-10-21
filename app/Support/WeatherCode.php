<?php

namespace App\Support;

class WeatherCode
{
    public static function label(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code === 1 => 'Mainly clear',
            $code === 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            in_array($code, [45, 48], true) => 'Fog',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'Rain',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'Snow',
            in_array($code, [95, 96, 99], true) => 'Thunderstorm',
            default => 'Unknown conditions',
        };
    }

    public static function group(int $code): string
    {
        return match (true) {
            $code === 0, $code === 1 => 'clear',
            in_array($code, [2, 3], true) => 'cloud',
            in_array($code, [45, 48], true) => 'fog',
            in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82], true) => 'rain',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'snow',
            in_array($code, [95, 96, 99], true) => 'storm',
            default => 'unknown',
        };
    }
}
