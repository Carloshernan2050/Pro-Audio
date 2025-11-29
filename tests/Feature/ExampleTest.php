<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // La ruta "/" redirige a 'inicio', así que esperamos un 302 (redirección)
        $response->assertStatus(302);
        $response->assertRedirect(route('inicio'));
    }
}
