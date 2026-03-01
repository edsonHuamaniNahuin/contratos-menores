<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La landing pública (/) responde HTTP 200.
     */
    public function test_landing_returns_ok(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * El dashboard redirige a login si no hay sesión.
     */
    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
