<?php

namespace Kwidoo\MultiAuth\Services;

use Kwidoo\MultiAuth\Contracts\OTPServiceInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Exception;

class TelegramService implements OTPServiceInterface
{

    /**
     * Generate and send an OTP via Telegram.
     *
     * @param string $username Telegram chat ID of the user
     *
     * @return void
     * @throws Exception
     */
    public function create(string $username): void
    {
        throw new Exception('Not implemented');
    }

    /**
     * Validate the OTP entered by the user.
     *
     * @param array $credentials [chat_id, entered_otp]
     *
     * @return bool
     * @throws OAuthServerException
     */
    public function validate(array $credentials): bool
    {
        throw new Exception('Not implemented');
    }
}
