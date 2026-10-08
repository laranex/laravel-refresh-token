---
name: laravel-refresh-token-development
description: >
  Issue, verify, rotate, revoke and prune RS256 JWT refresh tokens for Eloquent models in a Laravel app with laranex/laravel-refresh-token.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laravel Refresh Token

Use this skill when a Laravel API needs long-lived refresh tokens to renew short-lived access tokens (Sanctum, Passport or custom JWTs).

## Primary Goal

- issue and verify refresh tokens only through the package API, and revoke them on use (rotation) or logout

## Workflow

### 1. Set up keys and the table

- `php artisan refresh-token:keys` writes `storage/refresh-token-private.key` and `storage/refresh-token-public.key` (`--force` to replace, `--length=4096` default, minimum 2048)
- in production prefer `REFRESH_TOKEN_PRIVATE_KEY` / `REFRESH_TOKEN_PUBLIC_KEY` (PEM; `\n` sequences are expanded)
- `php artisan migrate` creates `laravel_refresh_tokens` (the migration is loaded automatically); publish only to change it: `php artisan vendor:publish --tag="refresh-token-migrations"`
- config keys are `private_key`, `public_key` and `table` only (`--tag="refresh-token-config"`)

### 2. Issue

- add `Laranex\RefreshToken\Concerns\HasRefreshTokens` to the model (e.g. `User`)
- `$jwt = $user->createRefreshToken();` returns the signed JWT string; the model must be saved
- change the lifetime once in a service provider: `RefreshToken::refreshTokensExpireIn(now()->addDays(30))` (default one year)

### 3. Verify and rotate

- `$token = Laranex\RefreshToken\RefreshToken::tokenable($jwt);` returns the `Laranex\RefreshToken\Models\RefreshToken` row or `null` (bad signature, expired, revoked, unknown)
- `$token->instance` is the owning model; then `$token->revoke()` and issue a new refresh token plus access token
- `$token->revokeAll()` revokes every token of that model (logout everywhere, suspected theft)
- `$user->refreshTokens()` lists a model's tokens

### 4. Prune

- schedule `refresh-token:prune` daily to delete expired and revoked rows

## Rules, References, and Templates

- no additional resource files for this skill

## Examples

- refresh endpoint: `$token = RefreshToken::tokenable($request->string('refresh_token')); abort_if($token === null, 401); $user = $token->instance; $token->revoke(); return ['access_token' => $user->createToken('api')->plainTextToken, 'refresh_token' => $user->createRefreshToken()];`
- custom model: extend `Laranex\RefreshToken\Models\RefreshToken` and register it with `RefreshToken::useRefreshTokenModel(MyRefreshToken::class)`

## Anti-patterns

- do not decode the JWT yourself to trust its claims; always go through `RefreshToken::tokenable()`
- do not commit `storage/refresh-token-*.key` or reuse the same keys across unrelated apps
- do not keep accepting a refresh token after it has been exchanged; revoke it
- do not add a `model` config key; it does not exist
