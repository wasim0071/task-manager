<?php

namespace App\Services;

use App\Exceptions\WeatherLookupException;
use App\Support\WeatherCode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WeatherService
{
    /**
     * Resolve a city and return current conditions plus a short daily forecast.
     * Successful lookups are cached so the public API is not called on every page view.
     *
     * @return array<string, mixed>
     */
    public function lookup(string $city): array
    {
        $displayCity = Str::of($city)->squish()->toString();
        $cacheKey = 'weather:'.Str::lower($displayCity);

        return Cache::remember(
            $cacheKey,
            now()->addSeconds((int) config('services.open_meteo.cache_ttl')),
            fn (): array => $this->fetch($displayCity),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(string $city): array
    {
        $place = $this->geocode($city);
        $forecast = $this->forecast($place);
        $current = is_array($forecast['current'] ?? null) ? $forecast['current'] : [];

        if (! isset($current['temperature_2m'])) {
            throw new WeatherLookupException('The weather service returned an incomplete forecast.');
        }

        $code = (int) ($current['weather_code'] ?? -1);

        return [
            'city' => (string) ($place['name'] ?? $city),
            'region' => isset($place['admin1']) ? (string) $place['admin1'] : null,
            'country' => isset($place['country']) ? (string) $place['country'] : null,
            'latitude' => round((float) $place['latitude'], 4),
            'longitude' => round((float) $place['longitude'], 4),
            'timezone' => (string) ($place['timezone'] ?? 'auto'),
            'observed_at' => isset($current['time']) ? (string) $current['time'] : null,
            'temperature_c' => round((float) $current['temperature_2m'], 1),
            'humidity' => isset($current['relative_humidity_2m']) ? (int) $current['relative_humidity_2m'] : null,
            'wind_speed_kmh' => isset($current['wind_speed_10m']) ? round((float) $current['wind_speed_10m'], 1) : null,
            'weather_code' => $code,
            'condition' => WeatherCode::label($code),
            'group' => WeatherCode::group($code),
            'daily' => $this->daily(is_array($forecast['daily'] ?? null) ? $forecast['daily'] : []),
            'source' => 'Open-Meteo',
            'attribution_url' => 'https://open-meteo.com/',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function geocode(string $city): array
    {
        $payload = $this->get(
            (string) config('services.open_meteo.geocoding_url'),
            [
                'name' => $city,
                'count' => 1,
                'language' => 'en',
                'format' => 'json',
            ],
        );

        $results = $payload['results'] ?? null;
        $place = is_array($results) ? ($results[0] ?? null) : null;

        if (! is_array($place) || ! isset($place['latitude'], $place['longitude'])) {
            throw new WeatherLookupException('No location matched that city.', 404);
        }

        return $place;
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    private function forecast(array $place): array
    {
        return $this->get(
            (string) config('services.open_meteo.forecast_url'),
            [
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'timezone' => is_string($place['timezone'] ?? null) ? $place['timezone'] : 'auto',
                'forecast_days' => 4,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $url, array $query): array
    {
        try {
            $response = Http::timeout(8)
                ->connectTimeout(5)
                ->acceptJson()
                ->get($url, $query);
        } catch (ConnectionException $exception) {
            Log::warning('Open-Meteo connection failed.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            throw new WeatherLookupException('The weather service did not respond. Try again in a moment.');
        }

        if ($response->failed()) {
            Log::warning('Open-Meteo returned an error.', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            throw new WeatherLookupException('The weather service did not respond. Try again in a moment.');
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $daily
     * @return list<array<string, mixed>>
     */
    private function daily(array $daily): array
    {
        $dates = is_array($daily['time'] ?? null) ? $daily['time'] : [];
        $days = [];

        foreach ($dates as $index => $date) {
            $code = (int) ($daily['weather_code'][$index] ?? -1);
            $days[] = [
                'date' => (string) $date,
                'weather_code' => $code,
                'condition' => WeatherCode::label($code),
                'group' => WeatherCode::group($code),
                'high_c' => isset($daily['temperature_2m_max'][$index]) ? round((float) $daily['temperature_2m_max'][$index], 1) : null,
                'low_c' => isset($daily['temperature_2m_min'][$index]) ? round((float) $daily['temperature_2m_min'][$index], 1) : null,
            ];
        }

        return $days;
    }
}
