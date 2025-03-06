<?php

namespace Kwidoo\MultiAuth\Resolvers;

use Kwidoo\MultiAuth\Contracts\UserResolver as UserResolverContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class GeneralUserResolver implements UserResolverContract
{
    /**
     * @param array $credentials
     *
     * @return Authenticatable|null
     */
    public function resolve(string $username, ?ClientEntityInterface $clientEntity = null, string $authMethod = 'password'): ?User
    {
        $provider = $clientEntity?->provider ?: config('auth.guards.api.provider');
        $model = config('auth.providers.' . $provider . '.model');

        $user = (new $model)->where('email', $username)->first();

        return $user ? new User($user->getAuthIdentifier()) : null;
    }
}
