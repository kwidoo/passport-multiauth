<?php

namespace Kwidoo\MultiAuth\Contracts;

interface OTPServiceInterface
{
    public function create(string $username): void;
    public function validate(array $credentials): bool;
}
