<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\HisabKitapDatabaseSeeder;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(HisabKitapDatabaseSeeder::class);
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
