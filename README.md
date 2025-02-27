# Laravel Passport Multi-Auth

A Laravel package that extends **Laravel Passport**'s password grant to support multiple authentication methods (SMS/Email/Telegram OTP, etc.). You can define custom OTP strategies and corresponding user resolvers, enabling flexible authentication flows in your Laravel application.

---

## Features

- **OTP Authentication**: Supports Twilio (SMS), Email, Telegram—or any custom service you define.
- **Config-Driven Setup**: Easily add or remove strategies in a single `passport-multiauth` config file.
- **Custom User Resolvers**: Associate each strategy with its own resolver (e.g., phone lookup, email lookup).
- **Seamless Integration with Laravel Passport**: Adds a custom grant type that you can use alongside the default password grant.

---

## Installation

1. **Install via Composer**:

   ```bash
   composer require kwidoo/multi-auth
   ```

2. **Publish the Configuration** (optional, but recommended to customize):

   ```bash
   php artisan vendor:publish --provider="Kwidoo\MultiAuth\MultiAuthServiceProvider" --tag=config
   ```

   This will publish a config file to `config/passport-multiauth.php`.

3. **Configure Your OAuth Server**:

   The package service provider automatically binds the custom `MultiAuthGrant` to your Laravel Passport `AuthorizationServer`. Just ensure you have set up [Laravel Passport](https://laravel.com/docs/passport) properly.

---

## Configuration

After publishing the config, you’ll have a file at `config/passport-multiauth.php`:

```php
return [
    'strategies' => [
        'twilio' => [
            'class'     => \Kwidoo\MultiAuth\Services\TwilioService::class,
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver'  => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,
        ],
        'email' => [
            'class'     => \Kwidoo\MultiAuth\Services\EmailService::class,
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver'  => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,
        ],
        'telegram' => [
            'class'     => \Kwidoo\MultiAuth\Services\TelegramService::class,
            'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
            'resolver'  => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,
        ],
    ],
];
```

### How the Config Works

- **`class`**: The service class that implements OTP generation/validation (e.g., TwilioService).
- **`strategy`**: The strategy wrapper (e.g., `OTPStrategy`) that implements `AuthStrategy`.
- **`resolver`**: Class that implements `Kwidoo\MultiAuth\Contracts\UserResolver`. Determines how to load the user after OTP validation.

You can add or remove strategies as needed—each entry must provide a `class`, a `strategy`, and a `resolver`.

---

## Usage

### 1. Request an OTP

This package includes an example `OTPController` to issue the OTP:

```php
POST /api/otp

{
    "method": "twilio",
    "username": "+1234567890"
}
```

- `method`: Must match one of the keys in `passport-multiauth.strategies` (e.g., `twilio`).
- `username`: The user identifier (phone, email, etc.).

### 2. Obtain Access Token via OAuth

You can then request a token from your Passport OAuth endpoint, typically `POST /oauth/token`, with:

```json
{
    "grant_type": "password",
    "client_id": "<client-id>",
    "client_secret": "<client-secret>",
    "username": "+1234567890",
    "password": "1234",
    "method": "twilio"
}
```

- `method`: Indicates which strategy to use. If omitted or set to `password`, it’ll fallback to the standard Laravel Passport username/password flow.

When `method` is set to a configured method (e.g. `twilio`), the package will:
1. Validate the OTP using the `TwilioService::validate()` method.
2. Resolve the user via `GeneralUserResolver`.
3. Issue the Passport token if validation and resolution succeed.

---

## Example Flow

1. **User enters phone number** in the app.
2. **App calls** `POST /api/otp` with `{"method":"twilio","username":"+1234567890"}`.
3. **`TwilioService::create()`** sends an SMS with a code.
4. **User receives code** on their phone and enters it in the app.
5. **App calls** `POST /oauth/token` with:
   ```json
   {
       "grant_type": "password",
       "client_id": "<client-id>",
       "client_secret": "<client-secret>",
       "username": "+1234567890",
       "password": "the-otp-code",
       "method": "twilio"
   }
   ```
6. **MultiAuthGrant** validates the OTP and fetches the user from the DB (via `GeneralUserResolver`).
7. **Passport** issues access token.

---

## Advanced Usage

### Custom Strategies

1. Create a class implementing `Kwidoo\MultiAuth\Contracts\OTPValidator` (and `OTPGenerator` if needed).
2. Create a class implementing `Kwidoo\MultiAuth\Contracts\AuthStrategy` to wrap validation logic (often you can reuse `OTPStrategy`).
3. Create (or reuse) a `Kwidoo\MultiAuth\Contracts\UserResolver` for user lookup logic.
4. Register them in your `passport-multiauth.php`:

```php
'my_custom_strategy' => [
    'class'     => \App\Services\MyCustomValidator::class,
    'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
    'resolver'  => \App\Resolvers\MyCustomResolver::class,
],
```

### Overriding the Default User Resolver

- If you want to resolve by phone number, or a different column, simply create a custom resolver:

```php
namespace App\Resolvers;

use Kwidoo\MultiAuth\Contracts\UserResolver;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Laravel\Passport\Bridge\User;

class PhoneUserResolver implements UserResolver
{
    public function resolve(string $username, ClientEntityInterface $clientEntity): ?User
    {
        $provider = $clientEntity->provider ?? config('auth.guards.api.provider');
        $model = config("auth.providers.$provider.model");

        $user = (new $model)->where('phone', $username)->first();

        return $user ? new User($user->getAuthIdentifier()) : null;
    }
}
```

Then in your config:

```php
'strategies' => [
    'twilio' => [
        'class'     => \Kwidoo\MultiAuth\Services\TwilioService::class,
        'strategy'  => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
        'resolver'  => \App\Resolvers\PhoneUserResolver::class,
    ],
    // ...
],
```

---

## Testing

1. **Unit Tests**: Mock your OTP services (Twilio, Email, etc.) to test the package’s logic without hitting real endpoints.
2. **Integration Tests**: Optionally test with real OTP services if you have test credentials.

---

## Contributing

Contributions, bug reports, and feature requests are welcome! Please open an issue or submit a pull request on [GitHub](https://github.com/your-github-handle/laravel-passport-multi-auth) if you find something you’d like to improve or extend.

---

## License

This package is open-source software licensed under the [MIT license](LICENSE). Feel free to modify and adapt to your needs.

---

Happy coding! If you run into any issues or have questions, please open a [GitHub Issue](https://github.com/your-github-handle/laravel-passport-multi-auth/issues).
