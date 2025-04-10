<?php

namespace Kwidoo\MultiAuth\Tests\Unit;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Contracts\OTPGeneratorInterface;
use Kwidoo\MultiAuth\Notifications\OTPNotification;
use Kwidoo\MultiAuth\Services\EmailVerifier;
use Kwidoo\MultiAuth\Tests\TestCase;
use Mockery;

class EmailServiceTest extends TestCase
{
    protected $cacheRepository;
    protected $channelManager;
    protected $otpGenerator;
    protected $emailVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->cacheRepository = Mockery::mock(Repository::class);
        $this->channelManager = Mockery::mock(ChannelManager::class);
        $this->otpGenerator = Mockery::mock(OTPGeneratorInterface::class);

        // Create service with mocked dependencies
        $this->emailVerifier = new EmailVerifier(
            $this->cacheRepository,
            $this->channelManager,
            $this->otpGenerator
        );

        // Clear cache between tests
        Cache::flush();

        // Fake notifications
        Notification::fake();
    }

    public function testCreateSendsEmailOTP()
    {
        $email = 'test@example.com';
        $code = '123456';

        // Setup expectations
        $this->otpGenerator->shouldReceive('generate')
            ->once()
            ->with(6)
            ->andReturn($code);

        $this->cacheRepository->shouldReceive('put')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email, $code, 300)
            ->andReturnTrue();

        // Call the method
        $this->emailVerifier->create($email);

        // Assert notification was sent
        Notification::assertSentTo(
            Notification::route('mail', $email),
            OTPNotification::class,
            function (OTPNotification $notification) use ($code) {
                return $notification->code === $code;
            }
        );
    }

    public function testValidateSuccessful()
    {
        $email = 'test@example.com';
        $code = '123456';

        // Setup expectations
        $this->cacheRepository->shouldReceive('has')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email)
            ->andReturnTrue();

        $this->cacheRepository->shouldReceive('get')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email)
            ->andReturn($code);

        $this->cacheRepository->shouldReceive('forget')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email)
            ->andReturnTrue();

        $result = $this->emailVerifier->validate([$email, $code]);

        $this->assertTrue($result);
    }

    public function testValidateFailsIfInvalidCode()
    {
        $email = 'test@example.com';
        $validCode = '123456';
        $wrongCode = 'wrong-code';

        $this->cacheRepository->shouldReceive('has')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email)
            ->andReturnTrue();

        $this->cacheRepository->shouldReceive('get')
            ->once()
            ->with('passport_multiauth_email_otp_' . $email)
            ->andReturn($validCode);

        $this->expectException(\League\OAuth2\Server\Exception\OAuthServerException::class);

        $this->emailVerifier->validate([$email, $wrongCode]);
    }
}
