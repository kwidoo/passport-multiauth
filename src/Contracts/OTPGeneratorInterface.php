<?php

namespace Kwidoo\MultiAuth\Contracts;

interface OTPGeneratorInterface
{
    /**
     * Generate a one-time password of specified length
     *
     * @param int $length
     * @return string
     */
    public function generate(int $length): string;
}
