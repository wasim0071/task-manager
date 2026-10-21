<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_cached_open_meteo_forecast(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [[
                    'name' => 'London',
                    'admin1' => 'England',
                    'country' => 'United Kingdom',
                    'latitude' => 51.5085,
                    'longitude' => -0.1257,
                    'timezone' => 'Europe/London',
                ]],
            ]),
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'time' => '2026-10-21T12:00',
                    'temperature_2m' => 16.24,
                    'relative_humidity_2m' => 70,
                    'weather_code' => 2,
                    'wind_speed_10m' => 12.2,
                ],
                'daily' => [
                    'time' => ['2026-10-21', '2026-10-22'],
                    'weather_code' => [2, 61],
                    'temperature_2m_max' => [18.2, 15.4],
                    'temperature_2m_min' => [10.1, 9.4],
                ],
            ]),
        ]);

        $this->getJson('/api/v1/integrations/weather?city=London')
            ->assertOk()
            ->assertJsonPath('data.city', 'London')
            ->assertJsonPath('data.country', 'United Kingdom')
            ->assertJsonPath('data.temperature_c', 16.2)
            ->assertJsonPath('data.condition', 'Partly cloudy')
            ->assertJsonPath('data.group', 'cloud')
            ->assertJsonPath('data.source', 'Open-Meteo')
            ->assertJsonPath('data.daily.1.condition', 'Rain');

        $this->getJson('/api/v1/integrations/weather?city=london')
            ->assertOk()
            ->assertJsonPath('data.city', 'London');

        Http::assertSentCount(2);
    }

    public function test_it_returns_not_found_when_the_city_does_not_match(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => []]),
        ]);

        $this->getJson('/api/v1/integrations/weather?city=Nowhereville')
            ->assertNotFound()
            ->assertJsonPath('message', 'No location matched that city.');
    }

    public function test_it_returns_a_gateway_error_when_the_provider_fails(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response(['message' => 'down'], 503),
        ]);

        $this->getJson('/api/v1/integrations/weather?city=London')
            ->assertStatus(502)
            ->assertJsonPath('message', 'The weather service did not respond. Try again in a moment.');
    }

    public function test_it_validates_the_city_query(): void
    {
        $this->getJson('/api/v1/integrations/weather')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city');

        $this->getJson('/api/v1/integrations/weather?city='.urlencode('<script>'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city');
    }
}
