# Laravel Refresh Token

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-refresh-token.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-refresh-token)

A package to help you implement refresh token mechanism in your laravel application

## Installation

You can install the package via composer:

```bash
  composer require laranex/laravel-refresh-token
```

Generate encryption keys
```bash
  php artisan refresh-token:keys
```

Run The migration file

```bash
  php artisan migrate
```

You can publish the config file with:

```bash
  php artisan vendor:publish --tag="refresh-token-config"
```

This is the contents of the published config file:

```php
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
        | Refresh Token Model
        |--------------------------------------------------------------------------
        |
        | Refresh Token Model to manage refresh tokens
        |
        */
        'model' => RefreshToken::class,
    
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
```

## Usage
- Use the trait in your refresh tokenable model

```php
    class User extends Authenticatable{
        use HasRefreshTokens;
    
    }
```

- ### Create a refresh token
```php
    $user = Auth::user()->createRefreshToken();
```

- ### Verify a refresh token
    - a token instance will be return if the token is valid, or else null will be return
```php
    $verifiedToken = Laranex\RefreshToken\RefreshToken::tokenable($request->get('refresh_token'));
    if ($verifiedToken) {
    // Implement your access token logic here
    
    } else {
    // handle invalid refresh token
    }
```

- ### Working with verified refresh token
    ```php
        $verifiedToken = Laranex\RefreshToken\RefreshToken::tokenable($request->get('refresh_token'));
    ```
    - You can access the token instance by calling the `instance` property, The instance property will return the model instance that you use the RefreshToken trait in
        ```php
            $tokenInstance = $verifiedToken->instance;
        ```
  
    - Revoking the refresh token (The token will no longer be valid)
      ```php
          $verifiedToken->revoke();
      ```
    - Revoking the refresh token (The token will no longer be valid)
      ```php
          $verifiedToken->revoke();
      ```
    - Revoking all refresh tokens which are related to current refresh token instance
      ```php
          $verifiedToken->revokeAll();
      ```
    

## Prune Command
- You can use the prune command to delete all expired refresh tokens
    ```bash
        php artisan refresh-token:prune
    ```
- Or you can put this into a scheduler to run it periodically
    ```php
        $schedule->command('refresh-token:prune')->daily();
    ```
  
## Overriding the default model
- You can override the default model by calling the `useRefreshTokenModel()` on the `Laranex\RefreshToken\RefreshToken\RefreshToken` class 
- Your custom model should extend `Laranex\RefreshToken\RefreshToken\RefreshToken` class

    ```php
        namespace App\Providers;
        
        use Illuminate\Support\ServiceProvider;
        
        class AppServiceProvider extends ServiceProvider
        {
            /**
             * Register any application services.
             */
            public function register(): void
            {
                Laranex\RefreshToken\RefreshToken::useRefreshTokenModel(YourCustomModel::class)
            }
        
            /**
             * Bootstrap any application services.
             */
            public function boot(): void
            {
                //
            }
}
    ```
    
## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nay Thu Khant](https://github.com/naythukhant)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
