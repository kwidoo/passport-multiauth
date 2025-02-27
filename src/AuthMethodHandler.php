<?php

namespace Kwidoo\MultiAuth;

use Kwidoo\Multiauth\Contracts\AuthStrategy;
use InvalidArgumentException;

class AuthMethodHandler
{
    /**
     * @param array<string, AuthStrategy> $authStrategies
     */
    public function __construct(protected array $authStrategies) {}

    /**
     * @param string $method
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(string $method, array $credentials): bool
    {
        $strategy = $this->getStrategy($method);

        return $strategy->validate($credentials);
    }

    protected function getStrategy(string $method): AuthStrategy
    {
        return $this->authStrategies[$method] ?? throw new InvalidArgumentException("Unsupported auth method: $method");
    }

    /**
     * @param string $method
     * @param AuthStrategy $strategy
     *
     * @return void
     */
    public function register(string $method, AuthStrategy $strategy): void
    {
        $this->authStrategies[$method] = $strategy;
    }
}
