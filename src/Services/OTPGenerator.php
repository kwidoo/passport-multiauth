<?php

namespace Kwidoo\MultiAuth\Services;

use Kwidoo\MultiAuth\Contracts\OTPGeneratorInterface;

class OTPGenerator implements OTPGeneratorInterface
{
    /**
     * Generate a cryptographically secure OTP of specified length
     *
     * @param int $length
     * @return string
     */
    public function generate(int $length = 6): string
    {
        return str_pad((string)random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
