<?php

namespace Kwidoo\MultiAuth\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;
use Kwidoo\MultiAuth\MultiAuthServiceProvider;
use Laravel\Passport\PassportServiceProvider;

abstract class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            PassportServiceProvider::class,
            MultiAuthServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app)
    {
        // Twilio config for tests
        $app['config']->set('twilio.sid', 'test_sid');
        $app['config']->set('twilio.auth_token', 'test_token');
        $app['config']->set('twilio.verify_sid', 'test_verify_sid');
    }
}
