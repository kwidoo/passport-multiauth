<?php

namespace Kwidoo\MultiAuth;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Kwidoo\MultiAuth\Grants\MultiAuthGrant;
use Kwidoo\MultiAuth\Http\Controllers\OTPController;
use Kwidoo\MultiAuth\Services\TwilioService;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Twilio\Rest\Client;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class MultiAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publish();
        $this->loadApplicationComponents();
        $this->registerAuthComponents();
        $this->bindExternalServices();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'passport-multiauth');
    }

    protected function publish(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('passport-multiauth.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/passport-multiauth'),
            ], 'views');
        }
    }

    protected function loadApplicationComponents(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'passport-multiauth');
    }

    protected function registerAuthComponents(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RefreshTokenRepositoryInterface::class, RefreshTokenRepository::class);
        $this->registerAuthMethodHandler();
        $this->registerAuthorizationGrant();
        $this->bindOTPController();
    }

    protected function registerAuthMethodHandler(): void
    {
        $this->app->singleton(AuthMethodHandler::class, function ($app) {
            $strategies = [];

            foreach (config('passport-multiauth.strategies', []) as $method => $entry) {
                $validator = $entry['class'] ?? null;
                $strategy = $entry['strategy'] ?? null;

                if ($validator && $strategy) {
                    $strategies[$method] = $app->make($strategy, [
                        'validator' => $app->make($validator)
                    ]);
                }
            }

            return new AuthMethodHandler($strategies);
        });
    }

    protected function registerAuthorizationGrant(): void
    {

        $this->app->resolving(AuthorizationServer::class, function (AuthorizationServer $server, $app) {
            $resolvers = $this->resolveStrategyImplementations('resolver');

            $server->enableGrantType(
                $app->make(MultiAuthGrant::class, [
                    'resolvers' => $resolvers
                ]),
                Passport::tokensExpireIn()
            );
        });
    }

    protected function bindOTPController(): void
    {
        $this->app->bind(OTPController::class, function () {
            $services = $this->resolveStrategyImplementations('class');
            return new OTPController($services);
        });
    }

    protected function resolveStrategyImplementations(string $type): array
    {
        $implementations = [];

        foreach (config('passport-multiauth.strategies', []) as $method => $entry) {
            $class = $entry[$type] ?? null;
            if ($class) {
                $implementations[$method] = $this->app->make($class);
            }
        }

        return $implementations;
    }

    protected function bindExternalServices(): void
    {
        $this->app->bind(Client::class, function () {
            return new Client(
                config('twilio.sid'),
                config('twilio.auth_token')
            );
        });

        $this->app->bind(TwilioService::class, function ($app) {
            return new TwilioService($app->make(Client::class));
        });
    }
}
