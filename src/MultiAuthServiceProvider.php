<?php

namespace Kwidoo\MultiAuth;

use Illuminate\Support\ServiceProvider;
use Kwidoo\MultiAuth\Contracts\OTPGeneratorInterface;
use Kwidoo\MultiAuth\Contracts\PasswordCheckerInterface;
use Kwidoo\MultiAuth\Factories\UserResolverFactory;
use Kwidoo\MultiAuth\Services\DefaultPasswordChecker;
use Kwidoo\MultiAuth\Services\OTPGenerator;
use Kwidoo\MultiAuth\Services\PasswordAwareOTPDecorator;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Kwidoo\MultiAuth\Grants\MultiAuthGrant;
use Kwidoo\MultiAuth\Http\Controllers\OTPController;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class MultiAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->validateConfig();
        $this->publish();
        $this->loadApplicationComponents();
        $this->registerAuthComponents();
        // $this->bindExternalServices();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/passport-multiauth.php', 'passport-multiauth');

        // Register core services
        $this->app->bind(OTPGeneratorInterface::class, OTPGenerator::class);
        $this->app->bind(PasswordCheckerInterface::class, DefaultPasswordChecker::class);
        $this->app->bind(UserResolverFactory::class);
    }

    protected function publish(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/passport-multiauth.php' => config_path('passport-multiauth.php'),
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
            $passwordChecker = $this->app->make(PasswordCheckerInterface::class);

            // Wrap each OTP service with the password check decorator
            foreach ($services as $method => $service) {
                $services[$method] = new PasswordAwareOTPDecorator($service, $passwordChecker);
            }

            return new OTPController($services, $this->app->make(config('passport-multiauth.strategies.email.resolver')));
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

    protected function validateConfig(): void
    {
        $strategies = config('passport-multiauth.strategies', []);

        if (empty($strategies)) {
            throw new \RuntimeException('No authentication strategies configured for passport-multiauth');
        }

        foreach ($strategies as $method => $entry) {
            if (!isset($entry['class']) || !isset($entry['strategy'])) {
                throw new \RuntimeException("Invalid strategy configuration for method '{$method}'");
            }
        }
    }
}
