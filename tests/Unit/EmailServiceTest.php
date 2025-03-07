<?php

namespace Kwidoo\MultiAuth\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Models\OTP;
use Kwidoo\MultiAuth\Notifications\OTPNotification;
use Kwidoo\MultiAuth\Services\EmailVerifier;
use Kwidoo\MultiAuth\Tests\TestCase;

class EmailVerifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrate the OTP table
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function testCreateSendsEmailOTP()
    {
        Notification::fake();

        $EmailVerifier = new EmailVerifier();
        $email = 'test@example.com';

        // This call should internally do something like:
        // Notification::route('mail', $email)->notify(new OTPNotification($code));
        $EmailVerifier->create($email);

        // Now assert
        Notification::assertSentTo(
            Notification::route('mail', $email),
            function (OTPNotification $notification, $channels) {
                return $notification instanceof OTPNotification; // Or however you handle the code
            }
        );

        $this->assertDatabaseHas('otps', [
            'username' => $email,
            'method'   => 'email'
        ]);
    }

    public function testValidateSuccessful()
    {
        // Create a dummy OTP record
        cache()->put('emailotptest@example.com', 123456, 5);

        $EmailVerifier = new EmailVerifier();
        $result = $EmailVerifier->validate(['test@example.com', '123456']);

        $this->assertTrue($result);
        $this->assertNotNull($otpRecord->fresh()->verified_at);
    }

    public function testValidateFailsIfInvalidCode()
    {
        $this->expectException(\League\OAuth2\Server\Exception\OAuthServerException::class);

        $EmailVerifier = new EmailVerifier();
        $EmailVerifier->validate(['test@example.com', 'wrong-code']);
    }
}
