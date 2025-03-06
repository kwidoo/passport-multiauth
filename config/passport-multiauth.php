<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'otp' => [
        'model'  => \Kwidoo\MultiAuth\Models\OTP::class,
        'length' => 6,
        'ttl'    => 5,
    ],
    'strategies' => [
        'phone' => [
            'class'     => \Kwidoo\SmsVerification\Contracts\VerifierInterface::class,  // Bind the interface with VerificationFactory
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,

        ],
        'email' => [
            'class'     => \Kwidoo\MultiAuth\Services\EmailService::class,
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,

        ],
    ],
];
