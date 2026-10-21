<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_shell_starts_with_the_assessment_code(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('IQT-FSD-2026', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_unknown_pages_offer_a_way_home(): void
    {
        $this->withoutVite();

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('Go to home', false)
            ->assertSee('href="'.url('/').'"', false);

        $this->getJson('/api/v1/not-a-real-route')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json');
    }
}
