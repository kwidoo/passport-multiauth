<?php

namespace Kwidoo\MultiAuth\Services;

use Kwidoo\MultiAuth\Contracts\AuthStrategy;
use Kwidoo\MultiAuth\Contracts\OTPServiceInterface;
use Kwidoo\SmsVerification\Contracts\VerifierInterface;

class OTPStrategy implements AuthStrategy
{
    public function __construct(protected OTPServiceInterface|VerifierInterface $validator) {}

    /**
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials): bool
    {
        return $this->validator->validate($credentials);
    }
}
