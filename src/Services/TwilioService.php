<?php

namespace Kwidoo\MultiAuth\Services;

use Twilio\Rest\Client;
use Kwidoo\MultiAuth\Contracts\OTPServiceInterface;
use League\OAuth2\Server\Exception\OAuthServerException;

class TwilioService implements OTPServiceInterface
{
    public function __construct(protected Client $client) {}

    /**
     * @param string $username Phone number for Twilio verification
     *
     * @return void
     */
    public function create(string $username): void
    {
        $sanitizedPhoneNumber = $this->sanitizePhoneNumber($username);

        $this->client->verify->v2->services(config('twilio.verify_sid'))
            ->verifications
            ->create($sanitizedPhoneNumber, 'sms');
    }


    /**
     * @param array $credentials [phone number, verification code]
     *
     * @return bool
     */
    public function validate(array $credentials): bool
    {
        $sanitizedPhoneNumber = $this->sanitizePhoneNumber($credentials[0]);

        $verification = $this->client
            ->verify
            ->v2
            ->services(config('twilio.verify_sid'))
            ->verificationChecks
            ->create([
                'to' => $sanitizedPhoneNumber,
                'code' => $credentials[1],
            ]);

        if (!$verification->valid) {
            throw OAuthServerException::invalidCredentials();
        }

        return true;
    }

    /**
     * Sanitize the phone number by ensuring only numbers and '+' are present.
     * If the phone number doesn't start with '+', add it.
     *
     * @param string $phoneNumber
     *
     * @return string
     */
    public function sanitizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (strpos($phoneNumber, '+') !== 0) {
            $phoneNumber = '+' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
