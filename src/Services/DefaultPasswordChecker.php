<?php

namespace Kwidoo\MultiAuth\Services;

use Illuminate\Support\Facades\DB;
use Kwidoo\MultiAuth\Contracts\PasswordCheckerInterface;
use Kwidoo\MultiAuth\Contracts\UserResolver;

class DefaultPasswordChecker implements PasswordCheckerInterface
{
    /**
     * @param UserResolver $userResolver
     */
    public function __construct(protected UserResolver $userResolver) {}

    /**
     * Check if a user has a password set
     *
     * @param string $username
     * @return bool
     */
    public function hasPassword(string $username): bool
    {
        $provider = config('auth.guards.api.provider');
        $model = config('auth.providers.' . $provider . '.model');

        if (!$model) {
            return false;
        }

        $user = (new $model)->where('email', $username)->first();

        if (!$user) {
            return false;
        }

        return !empty($user->password);
    }
}
