<?php

namespace Kwidoo\MultiAuth\Services;

use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Notifications\OTPNotification;
use Kwidoo\SmsVerification\Contracts\VerifierInterface;
use Kwidoo\SmsVerification\Exceptions\VerifierException;
use League\OAuth2\Server\Exception\OAuthServerException;

class EmailVerifier implements VerifierInterface
{
    /**
     * @param string $username Email for OTP verification
     *
     * @return void
     */
    public function create(string $username): void
    {
        $code = $this->generateOTP(config('passport-multiauth.otp.length'));
        cache()->put("emailotp$username", $code, config('passport-multiauth.otp.ttl'));

        Notification::route('mail', $username)
            ->notify(new OTPNotification($code));
    }

    /**
     * @param array $credentials [email, verification code]
     *
     * @return bool
     */
    public function validate(array $credentials): bool
    {
        if (count($credentials) < 2) {
            throw new VerifierException('Credentials array must include [phoneNumber, code].');
        }

        [$username, $verificationCode] = $credentials;
        $code = cache()->pull("emailotp$username");
        if (!$code || $code !== $verificationCode) {
            throw OAuthServerException::invalidCredentials();
        }


        return true;
    }

    /**
     * @param int $length
     *
     * @return string
     * @todo change to something more reliable
     */
    protected function generateOTP(int $length = 6): string
    {
        return str_pad((string)rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
