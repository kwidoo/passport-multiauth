<?php

namespace Kwidoo\PassportMultiAuth;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kwidoo\PassportMultiAuth\Skeleton\SkeletonClass
 */
class PassportMultiAuthFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'passport-multi-auth';
    }
}
