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
}
