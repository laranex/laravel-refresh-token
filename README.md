# Laravel Refresh Token

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)
[![Tests](https://github.com/laranex/laravel-refresh-token/actions/workflows/tests.yml/badge.svg)](https://github.com/laranex/laravel-refresh-token/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)
[![License](https://img.shields.io/packagist/l/laranex/laravel-refresh-token.svg?style=flat-square)](LICENSE.md)

Refresh tokens for Laravel: issue, verify, revoke and prune RS256 JWT refresh tokens for any Eloquent model. Built for humans and AI agents.

Short-lived access tokens are safer, but nobody wants to sign in again every hour. A refresh token lets the client get a new access token without the password. Laravel Refresh Token issues refresh tokens as signed JWTs that are also stored in your database, so you can revoke any of them at any time.

## Why Laravel Refresh Token

- **Signed and stored.** Each token is an RS256 JWT, checked for its signature and its `iat`, `nbf` and `exp` claims, and backed by a database row, so revoking it takes effect immediately.
- **Rotation that survives races.** `revoke()` is one conditional update, so when two requests exchange the same token at once, only one of them wins.
- **Revoke everything at once.** `revokeRefreshTokens()` signs a model out everywhere after a password change, without needing a token.
- **Any Eloquent model.** Add the `HasRefreshTokens` trait to any model; tokens are stored with its morph class.
- **Keys from files or the environment.** `php artisan refresh-token:keys` writes an RSA key pair, or set the PEM contents in environment variables in production.
- **Housekeeping included.** `refresh-token:prune` deletes expired and revoked tokens in chunks, and a factory with `expired()` and `revoked()` states helps your tests.
- **Works with any access token.** It issues refresh tokens only, so pair it with Sanctum, Passport or your own access tokens.

## Installation

Requires PHP 8.1+, Laravel 10, 11, 12 or 13, and the `openssl` and `sodium` PHP extensions.

```bash
composer require laranex/laravel-refresh-token
```

## Documentation

The full documentation lives at **[laranex.vercel.app/laravel-refresh-token](https://laranex.vercel.app/laravel-refresh-token)**:

- [Laravel Refresh Token introduction](https://laranex.vercel.app/laravel-refresh-token/introduction): why use it and what it does
- [Installing Laravel Refresh Token](https://laranex.vercel.app/laravel-refresh-token/installation): Composer, the RSA keys and the migration
- [Laravel Refresh Token AI agent skill](https://laranex.vercel.app/laravel-refresh-token/ai-agent-skill): the skill for Claude Code, Codex, Cursor and other coding agents
- [Laravel Refresh Token configuration](https://laranex.vercel.app/laravel-refresh-token/configuration): keys, table, model, key path and token lifetime
- [Upgrading Laravel Refresh Token](https://laranex.vercel.app/laravel-refresh-token/upgrading): from v1 to v4
- [Laravel Refresh Token usage](https://laranex.vercel.app/laravel-refresh-token/usage): create, verify, rotate, revoke and prune refresh tokens, and test with the factory

## License

MIT. See [LICENSE.md](LICENSE.md).
