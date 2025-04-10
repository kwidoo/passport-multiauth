<?php

namespace Kwidoo\MultiAuth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Contracts\OTPGeneratorInterface;
use Kwidoo\MultiAuth\Notifications\OTPNotification;

class EmailVerifier extends AbstractOTPVerifier
{
    /**
     * @param CacheRepository $cache
     * @param ChannelManager $notifications
     * @param OTPGeneratorInterface $otpGenerator
     */
    public function __construct(
        protected CacheRepository $cache,
        protected ChannelManager $notifications,
        protected OTPGeneratorInterface $otpGenerator
    ) {
        parent::__construct($cache);
    }

    /**
     * Send OTP via email
     *
     * @param string $username Email address
     * @param string $code OTP code
     * @return void
     */
    protected function sendOTP(string $username, string $code): void
    {
        Notification::route('mail', $username)
            ->notify(new OTPNotification($code));
    }

    /**
     * @return string
     */
    protected function getCacheKeyPrefix(): string
    {
        return 'passport_multiauth_email_otp_';
    }

    /**
     * @param int $length
     * @return string
     */
    protected function generateOTP(int $length = 6): string
    {
        return $this->otpGenerator->generate($length);
    }
}
