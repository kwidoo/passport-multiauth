<?php

namespace Kwidoo\MultiAuth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\MultiAuth\Tests\TestCase;

class OTPControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function testCreateOtpEndpoint()
    {
        $response = $this->postJson('/oauth/otp', [
            'method' => 'email',
            'username' => 'user@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('otps', [
            'username' => 'user@example.com',
            'method' => 'email',
        ]);
    }

    public function testCreateOtpEndpointFailsWithoutMethod()
    {
        $response = $this->postJson('/oauth/otp', [ 
            'username' => 'user@example.com',
        ]);

        $response->assertStatus(422); // validation error
    }

    public function testCreateOtpEndpointFailsOnUnsupportedMethod()
    {
        // Instead of expecting an exception, we assert the HTTP response (assuming you return 422).
        $response = $this->postJson('/oauth/otp', [
            'method' => 'invalid',
            'username' => 'user@example.com',
        ]);

        $response->assertStatus(422);
    }
}
