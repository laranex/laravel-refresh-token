---
name: laravel-refresh-token
description: >
  Issue, verify, rotate, revoke and prune RS256 JWT refresh tokens for Eloquent models in a Laravel app with laranex/laravel-refresh-token.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laravel Refresh Token

## When to use

Use this skill when a Laravel API needs long-lived refresh tokens to renew short-lived access tokens (Sanctum, Passport or your own JWTs) with `laranex/laravel-refresh-token`. Tokens are RS256-signed JWTs backed by a database row: issue and verify them only through the package API, and revoke them when they are used (rotation) or on logout.

## Install

```bash
composer require laranex/laravel-refresh-token
php artisan refresh-token:keys
php artisan migrate
```

Requires PHP 8.1+ (with `openssl` and `sodium`) and Laravel 10 to 13. The service provider is auto-discovered and the migration that creates `laravel_refresh_tokens` is loaded automatically.

## Configure

- `php artisan refresh-token:keys` writes `storage/refresh-token-private.key` and `storage/refresh-token-public.key`. `--force` replaces existing keys, `--length=4096` sets the RSA key size (minimum 2048).
- In production prefer `REFRESH_TOKEN_PRIVATE_KEY` and `REFRESH_TOKEN_PUBLIC_KEY` (PEM contents; literal `\n` sequences are expanded).
- Load the key files from another folder with `RefreshToken::loadKeysFrom($path)` in a service provider.
- The config keys are `private_key`, `public_key` and `table`. Publish only to change them: `php artisan vendor:publish --tag="refresh-token-config"`; `--tag="refresh-token-migrations"` publishes the migration.
- Change the lifetime once in a service provider: `RefreshToken::refreshTokensExpireIn(now()->addDays(30))` (default one year). The date must be in the future; a past date or the current moment throws an `InvalidArgumentException`.
- Use your own model by extending `Laranex\RefreshToken\Models\RefreshToken` and registering it with `RefreshToken::useRefreshTokenModel(MyRefreshToken::class)`.

## Use

### Issue

Add `Laranex\RefreshToken\Concerns\HasRefreshTokens` to the model (for example `User`), then:

```php
$refreshToken = $user->createRefreshToken(); // signed JWT string; the model must be saved
```

`$user->refreshTokens()` is the `MorphMany` relation of every token issued to the model.

### Verify and rotate

```php
use Laranex\RefreshToken\RefreshToken;

$token = RefreshToken::tokenable($request->string('refresh_token')->toString());
abort_if($token === null, 401); // bad signature, expired, revoked or unknown

// revoke() is atomic: when two requests race with the same token, only one gets true.
abort_unless($token->revoke(), 401);

$user = $token->instance;

return [
    'access_token' => $user->createToken('api')->plainTextToken,
    'refresh_token' => $user->createRefreshToken(),
];
```

`$token->revokeAll()` revokes every token of the same model (logout everywhere, suspected theft).

### Prune

Schedule `refresh-token:prune` daily; it deletes expired and revoked rows.

```php
Schedule::command('refresh-token:prune')->daily();
```

## Test your app

- Build tokens with `Laranex\RefreshToken\Models\RefreshToken::factory()`; the `expired()` and `revoked()` states cover the failure paths. Attach a token to a real model with `->for($user, 'instance')`.
- For end-to-end tests call `$user->createRefreshToken()` and post the JWT to your refresh endpoint; generate test keys once with `php artisan refresh-token:keys` or set the env keys in `phpunit.xml`.
- Use `$this->travel(...)` or `Carbon::setTestNow()` to expire tokens; the package clock follows Carbon.

## Avoid

- Decoding the JWT yourself and trusting its claims; always go through `RefreshToken::tokenable()`.
- Committing `storage/refresh-token-*.key` or sharing the same keys across unrelated apps.
- Accepting a refresh token again after it has been exchanged; revoke it and reject the request when `revoke()` returns `false`.
- Adding a `model` config key; it does not exist. Use `RefreshToken::useRefreshTokenModel()`.
