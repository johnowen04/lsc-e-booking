<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Database\Seeders\TestDatabaseSeeder;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $response = $this->get('/booking-schedule');

        $response->assertStatus(200);
    }
}
