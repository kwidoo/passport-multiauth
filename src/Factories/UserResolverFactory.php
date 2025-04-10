<?php

namespace Kwidoo\MultiAuth\Factories;

use Illuminate\Contracts\Container\Container;
use Kwidoo\MultiAuth\Contracts\UserResolver;

class UserResolverFactory
{
    /**
     * @param Container $container
     */
    public function __construct(protected Container $container) {}

    /**
     * Create a user resolver for the specified authentication method
     *
     * @param string $method Authentication method
     * @return UserResolver
     * @throws \InvalidArgumentException If no resolver is configured
     */
    public function make(string $method): UserResolver
    {
        $resolverClass = config("passport-multiauth.strategies.{$method}.resolver");

        if (!$resolverClass || !class_exists($resolverClass)) {
            throw new \InvalidArgumentException("No resolver configured for auth method: {$method}");
        }

        return $this->container->make($resolverClass);
    }
}
