<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'otp' => [
        'length' => 6,
        'ttl'    => 300,  // seconds
    ],
    'strategies' => [
        'phone' => [
            'class'     => \Kwidoo\SmsVerification\Contracts\VerifierInterface::class,  // Bind the interface with VerificationFactory
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,

        ],
        'email' => [
            'class'     => \Kwidoo\MultiAuth\Services\EmailVerifier::class,
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,

        ],
    ],
];
