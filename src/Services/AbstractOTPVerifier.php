<?php

namespace Kwidoo\MultiAuth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Kwidoo\SmsVerification\Contracts\VerifierInterface;
use Kwidoo\SmsVerification\Exceptions\VerifierException;
use League\OAuth2\Server\Exception\OAuthServerException;

abstract class AbstractOTPVerifier implements VerifierInterface
{
    public function __construct(protected CacheRepository $cache) {}

    abstract protected function sendOTP(string $username, string $code): void;

    abstract protected function getCacheKeyPrefix(): string;

    public function create(string $username): void
    {
        $code = $this->generateOTP(config('passport-multiauth.otp.length'));
        $this->cache->put(
            $this->getCacheKey($username),
            $code,
            config('passport-multiauth.otp.ttl')
        );

        $this->sendOTP($username, $code);
    }

    public function validate(array $credentials): bool
    {
        if (count($credentials) < 2) {
            throw new VerifierException('Credentials array must include [username, code].');
        }

        [$username, $verificationCode] = $credentials;
        $code = $this->cache->pull($this->getCacheKey($username));

        if (!$code || $code !== $verificationCode) {
            throw OAuthServerException::invalidCredentials();
        }

        return true;
    }

    protected function getCacheKey(string $username): string
    {
        return $this->getCacheKeyPrefix() . md5($username);
    }

    protected function generateOTP(int $length = 6): string
    {
        return str_pad((string)random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
