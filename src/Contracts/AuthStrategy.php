<?php

namespace Kwidoo\MultiAuth\Contracts;

interface AuthStrategy
{
    /**
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials): bool;
}
