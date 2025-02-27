<?php

namespace Kwidoo\MultiAuth\Services;

use Illuminate\Support\Facades\Notification;
use Kwidoo\MultiAuth\Contracts\OTPServiceInterface;
use Kwidoo\MultiAuth\Notifications\OTPNotification;
use League\OAuth2\Server\Exception\OAuthServerException;

class EmailService implements OTPServiceInterface
{
    /**
     * @var string
     */
    protected string $model;

    public function __construct()
    {
        $this->model = config('passport-multiauth.otp.model');
    }

    /**
     * @param string $username Email for OTP verification
     *
     * @return void
     */
    public function create(string $username): void
    {
        $otp = ($this->model)::create([
            'code' => $this->generateOTP(config('passport-multiauth.otp.length')),
            'username' => $username,
            'method' => 'email',
            'expires_at' => now()->addMinutes(config('passport-multiauth.otp.ttl')),
        ]);

        Notification::route('mail', $username)
            ->notify(new OTPNotification($otp->code));
    }

    /**
     * @param array $credentials [email, verification code]
     *
     * @return bool
     */
    public function validate(array $credentials): bool
    {
        $otp = ($this->model)::where('username', $credentials[0])
            ->where('code', $credentials[1])
            ->where('expires_at', '>=', now())
            ->whereNull('verified_at')
            ->first();

        if (!$otp) {
            throw OAuthServerException::invalidCredentials();
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function generateOTP(int $length = 6): string
    {
        return str_pad((string)rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
