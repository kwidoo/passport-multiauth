<?php

namespace Kwidoo\MultiAuth\Services;

use Kwidoo\MultiAuth\Contracts\PasswordCheckerInterface;
use Kwidoo\SmsVerification\Contracts\VerifierInterface;

/**
 * Decorator for OTP services that checks if a user has a password
 * before allowing OTP generation
 */
class PasswordAwareOTPDecorator implements VerifierInterface
{
    /**
     * @param VerifierInterface $otpService The underlying OTP service
     * @param PasswordCheckerInterface $passwordChecker Service to check if user has a password
     */
    public function __construct(
        protected VerifierInterface $otpService,
        protected PasswordCheckerInterface $passwordChecker
    ) {}

    /**
     * Create and send OTP if user doesn't have a password
     *
     * @param string $username
     * @return bool True if OTP was sent, false if user has a password and OTP was not sent
     */
    public function create(string $username): bool
    {
        // If user has a password, don't send OTP
        if ($this->passwordChecker->hasPassword($username)) {
            return false;
        }

        // Otherwise, delegate to the decorated service
        $this->otpService->create($username);
        return true;
    }

    /**
     * Validate OTP code by delegating to decorated service
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials): bool
    {
        return $this->otpService->validate($credentials);
    }
}
