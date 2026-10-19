<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // The root route deliberately redirects to the login page (there is no
        // public landing page); authenticated users continue to the dashboard
        // via the guest middleware on /login.
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
