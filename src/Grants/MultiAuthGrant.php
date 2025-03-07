<?php

namespace Kwidoo\MultiAuth\Grants;

use Kwidoo\MultiAuth\Contracts\UserResolver;
use Kwidoo\MultiAuth\AuthMethodHandler;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use RuntimeException;

class MultiAuthGrant extends PasswordGrant
{
    /**
     * @param AuthMethodHandler $authHandler
     * @param array<string, UserResolver> $resolvers
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        protected AuthMethodHandler $authHandler,
        protected array $resolvers
    ) {
        parent::__construct($userRepository, $refreshTokenRepository);
    }

    public function registerResolver(string $method, UserResolver $resolver)
    {
        $this->resolvers[$method] = $resolver;
    }
    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface $clientEntity
     *
     * @return User
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $clientEntity): User
    {
        // Original Laravel Passport grant
        $authMethod = $this->getRequestParameter('method', $request, 'password');

        if ($authMethod === 'password') {
            return parent::validateUser($request, $clientEntity);
        }

        // Extended grant with OTP verification
        $provider = $clientEntity->provider ?: config('auth.guards.api.provider');
        $model = config('auth.providers.' . $provider . '.model');

        if (is_null($model)) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        $credentials = [
            $this->getRequestParameter('username', $request),
            $this->getRequestParameter('password', $request)
        ];

        if (!$this->authHandler->validate($authMethod, $credentials)) {
            throw OAuthServerException::invalidCredentials();
        }

        return $this->resolvers[$authMethod]->resolve(
            $credentials[0],
            $clientEntity,
            $authMethod
        );
    }
}
