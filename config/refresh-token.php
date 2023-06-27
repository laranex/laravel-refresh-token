<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Refresh Token uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */
    'private_key' => env('REFRESH_TOKEN_PRIVATE_KEY'),

    'public_key' => env('REFRESH_TOKEN_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Table
    |--------------------------------------------------------------------------
    |
    | Refresh Token Model to manage refresh tokens
    |
    */
    'table' => 'laravel_refresh_tokens',
];
