<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Refresh tokens are RS256 JWTs signed with an RSA key pair. By default the
    | keys are read from `refresh-token-private.key` and `refresh-token-public.key`
    | in the storage path (generate them with `php artisan refresh-token:keys`),
    | but the PEM contents may also be provided through environment variables
    | when that is more convenient. Literal "\n" sequences are expanded.
    |
    */

    'private_key' => env('REFRESH_TOKEN_PRIVATE_KEY'),

    'public_key' => env('REFRESH_TOKEN_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Table
    |--------------------------------------------------------------------------
    |
    | The database table that stores issued refresh tokens.
    |
    */

    'table' => 'laravel_refresh_tokens',

];
