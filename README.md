# Laravel Passport Multi-Auth

A Laravel package that **extends Laravel Passport’s** password grant to support multiple authentication methods (e.g., SMS/Email OTP). You can define custom OTP strategies and user resolvers in a single config file, making your OTP flows more flexible and secure.

## Requirements

- **Laravel Passport** (already required by this package).
- **PHP** ^8.0
- **(Optional) Twilio SDK** if you plan to use Twilio for SMS OTP:
  ```bash
  composer require twilio/sdk
  ```
  Note: For twilio in production you should install Twilio SDK.


---

## Installation

1. **Require via Composer**:
   ```bash
   composer require kwidoo/passport-multiauth
   ```
   This automatically installs the package and its dependencies (except for the optional Twilio SDK mentioned above).

2. **Publish Config (optional)**:
   ```bash
   php artisan vendor:publish --provider="Kwidoo\MultiAuth\MultiAuthServiceProvider" --tag=config
   ```
   This copies `passport-multiauth.php` into your `config` folder, allowing you to adjust OTP strategies, timeouts, and more.

3. **Publish Migrations and Views** (if desired):
   ```bash
   php artisan vendor:publish --provider="Kwidoo\MultiAuth\MultiAuthServiceProvider" --tag=migrations
   php artisan vendor:publish --provider="Kwidoo\MultiAuth\MultiAuthServiceProvider" --tag=views
   ```
   - Migrations will create (by default) an `otps` table to store OTP codes and status.
   - Views include basic Blade templates for OTP success/error states.

4. **Run Migrations**:
   ```bash
   php artisan migrate
   ```
   This will create any necessary tables (e.g., `otps` table for storing email OTPs).

5. **Configure Twilio (Optional)**:
   If using Twilio, define these in your `.env` or in a `config/twilio.php`:
   ```env
   TWILIO_SID=your_twilio_sid
   TWILIO_AUTH_TOKEN=your_twilio_auth_token
   TWILIO_VERIFY_SID=your_twilio_verify_service_id
   ```
   Note, you should have a Twilio account and will need to create Verification SID there. This code doesn't use regular SMS by default, but Twilio Verify API.
   For more details, see the [Twilio Verify API](https://www.twilio.com/docs/verify/api).
---

## Usage

1. **Request an OTP**
   This package includes `OTPController` and a route file (`routes.php`) with a `POST /oauth/otp` endpoint by default. Package will respect changes in `config/passport.php` file.
   Example request:
   ```json
   POST /oauth/otp
   {
     "method": "twilio",
     "username": "+1234567890"
   }
   ```
   or:
   ```json
   POST /oauth/otp
   {
     "method": "email",
     "username": "user@example.com"
   }
   ```
   This triggers the corresponding service (e.g., `TwilioService` or `EmailService`) to generate/send OTP.

2. **Obtain Access Token**
   After receiving the OTP, call the standard Passport token endpoint (often `POST /oauth/token`) with parameters:
   ```json
   {
     "grant_type": "password",
     "client_id": "your-passport-client-id",
     "client_secret": "your-passport-client-secret",
     "username": "+1234567890",
     "password": "123456",  // The OTP from Twilio
     "method": "twilio"
   }
   ```
   - If `method` = `"twilio"`, the package will validate the OTP via Twilio.
   - If `method` = `"email"`, it will use the email-based OTP.
   - If `method` is `"password"` or missing, it defaults to **Laravel Passport’s** normal password-based grant.

3. **Configuration File** (`config/passport-multiauth.php`)
   You can customize each strategy like so:
   ```php
   'strategies' => [
       'twilio' => [
           'class'    => \Kwidoo\MultiAuth\Services\TwilioService::class,
           'strategy' => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
           'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,
       ],
       'email' => [
           'class'    => \Kwidoo\MultiAuth\Services\EmailService::class,
           'strategy' => \Kwidoo\MultiAuth\Services\OTPStrategy::class,
           'resolver' => \Kwidoo\MultiAuth\Resolvers\GeneralUserResolver::class,
       ],
       // ... or custom strategies
   ],
   ```
   - **`class`**: The service that sends/validates the OTP (Twilio, Email, etc.).
   - **`strategy`**: Usually `OTPStrategy`, which handles how the credentials are validated using the `class`.
   - **`resolver`**: Defines how to find your user record after OTP validation (e.g., by email, phone).

4. **OTP model** (`Kwidoo\MultiAuth\Models\OTP`)
   - This model is used on with Email strategy to store OTPs. Twilio doesn't require to store OTPs localy
   - You can customize one time password model by changing the `otp.model` key in the configuration file.

   - Default one time password is 6 digits long and expires in 5 minutes. You can change these values in the configuration file.
---

## Example Flow

1. **User** enters their phone number on your app.
2. **Your app** calls `POST /oauth/otp` with `{ "method":"twilio", "username":"+1234567890" }`.
3. **TwilioService** (in the background) sends an SMS code via Twilio.
4. **User** receives the code and enters it in your app.
5. **Your app** calls `POST /oauth/token` with:
   ```json
   {
     "grant_type": "password",
     "client_id": "...",
     "client_secret": "...",
     "username": "+1234567890",
     "password": "the-otp-code",
     "method": "twilio"
   }
   ```
6. **MultiAuthGrant** checks that the OTP is correct, then resolves the user from the database, and **issues a Passport token**.

---

## Testing

### Running Tests Locally

1. Install dependencies & dev tools (like Orchestra Testbench):
   ```bash
   composer install
   ```
2. Run tests:
   ```bash
   composer test
   ```
   or
   ```bash
   vendor/bin/phpunit
   ```
3. Generate coverage:
   ```bash
   composer test-coverage
   ```
   This outputs an HTML coverage report in a `coverage` folder.

## Contributing

- Feel free to open issues or submit pull requests on [GitHub](https://github.com/kwidoo/passport-multi-auth).
- All contributions are welcome and appreciated.

## License

[MIT](LICENSE)

---

**That’s it!** With the updated README and example tests, you should have a clearer path to install, configure, and verify functionality for the Laravel Passport Multi-Auth package. If you need any additional help, let us know. Happy coding!
