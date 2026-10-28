<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_displays_the_public_mediway_homepage(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Chủ động chăm sóc sức khỏe');
    }
}
