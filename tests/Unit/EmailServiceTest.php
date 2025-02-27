<?php

namespace Kwidoo\MultiAuth\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Models\OTP;
use Kwidoo\MultiAuth\Notifications\OTPNotification;
use Kwidoo\MultiAuth\Services\EmailService;
use Kwidoo\MultiAuth\Tests\TestCase;

class EmailServiceTest extends TestCase
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

        $emailService = new EmailService();
        $email = 'test@example.com';

        // This call should internally do something like:
        // Notification::route('mail', $email)->notify(new OTPNotification($code));
        $emailService->create($email);

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
        $otpRecord = OTP::create([
            'code'       => '123456',
            'username'   => 'test@example.com',
            'method'     => 'email',
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $emailService = new EmailService();
        $result = $emailService->validate(['test@example.com', '123456']);

        $this->assertTrue($result);
        $this->assertNotNull($otpRecord->fresh()->verified_at);
    }

    public function testValidateFailsIfInvalidCode()
    {
        $this->expectException(\League\OAuth2\Server\Exception\OAuthServerException::class);

        $emailService = new EmailService();
        $emailService->validate(['test@example.com', 'wrong-code']);
    }
}
