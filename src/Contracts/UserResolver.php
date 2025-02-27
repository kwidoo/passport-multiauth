<?php

namespace Kwidoo\MultiAuth\Contracts;

use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;

interface UserResolver
{
    public function resolve(string $username, ?ClientEntityInterface $clientEntity = null): ?User;
}
