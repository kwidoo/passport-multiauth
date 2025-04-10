<?php

namespace Kwidoo\MultiAuth\Contracts;

interface PasswordCheckerInterface
{
    /**
     * Check if a user has a password set
     *
     * @param string $username
     * @return bool True if user has a password, false otherwise
     */
    public function hasPassword(string $username): bool;
}
