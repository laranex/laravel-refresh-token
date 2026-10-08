# Laravel Refresh Token

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranex/laravel-refresh-token/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/laranex/laravel-refresh-token/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)
[![License](https://img.shields.io/packagist/l/laranex/laravel-refresh-token.svg?style=flat-square)](LICENSE.md)

Issue, verify, revoke and prune long-lived refresh tokens for any Eloquent model. Tokens are RS256-signed JWTs backed by a database row, so they can be checked offline by signature and still be revoked one by one or all at once. It is meant for Laravel APIs that hand out short-lived access tokens (Sanctum, Passport, your own JWTs) and need a safe way to renew them.

## Documentation

Full documentation lives at **[laranex.vercel.app/laravel-refresh-token](https://laranex.vercel.app/laravel-refresh-token)**.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, 12 or 13

## Installation

```bash
composer require laranex/laravel-refresh-token
```

Generate the RSA key pair (written to `storage/refresh-token-private.key` and `storage/refresh-token-public.key`) and run the migration, which the package loads automatically:

```bash
php artisan refresh-token:keys
php artisan migrate
```

Optionally publish the config (`private_key`, `public_key`, `table`) or the migration:

```bash
php artisan vendor:publish --tag="refresh-token-config"
php artisan vendor:publish --tag="refresh-token-migrations"
```

In production you may set `REFRESH_TOKEN_PRIVATE_KEY` and `REFRESH_TOKEN_PUBLIC_KEY` to the PEM contents instead of shipping key files.

## Usage

```php
use Laranex\RefreshToken\Concerns\HasRefreshTokens;
use Laranex\RefreshToken\RefreshToken;

class User extends Authenticatable
{
    use HasRefreshTokens;
}

// Issue: returns a signed JWT (valid for one year by default)
$refreshToken = $user->createRefreshToken();

// Verify: returns the token model, or null when it is invalid, expired or revoked
$token = RefreshToken::tokenable($request->input('refresh_token'));

if ($token === null) {
    abort(401);
}

$user = $token->instance;   // the model the token was issued for
$token->revoke();           // rotate: revoke this token...
$token->revokeAll();        // ...or every token of this user

// Optional, e.g. in AppServiceProvider::boot()
RefreshToken::refreshTokensExpireIn(now()->addDays(30));
RefreshToken::useRefreshTokenModel(MyRefreshToken::class);
RefreshToken::loadKeysFrom(base_path('secrets'));
```

Delete expired and revoked tokens on a schedule with `Schedule::command('refresh-token:prune')->daily();`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Nay Thu Khant](https://github.com/NayThuKhant)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
