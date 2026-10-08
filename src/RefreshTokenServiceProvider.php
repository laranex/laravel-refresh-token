<?php

declare(strict_types=1);

namespace Laranex\RefreshToken;

use Illuminate\Support\ServiceProvider;
use Laranex\RefreshToken\Console\Commands\KeysCommand;
use Laranex\RefreshToken\Console\Commands\PruneCommand;

class RefreshTokenServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/refresh-token.php', 'refresh-token');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/refresh-token.php' => $this->app->configPath('refresh-token.php'),
        ], ['refresh-token', 'refresh-token-config']);

        $this->publishes([
            __DIR__.'/../database/migrations/create_laravel_refresh_tokens_table.php' => $this->migrationTarget(),
        ], ['refresh-token', 'refresh-token-migrations']);

        $this->commands([
            KeysCommand::class,
            PruneCommand::class,
        ]);
    }

    /**
     * Reuse an already published copy of the migration instead of publishing a second one.
     */
    private function migrationTarget(): string
    {
        $existing = glob($this->app->databasePath('migrations/*_create_laravel_refresh_tokens_table.php'));

        if (is_array($existing) && $existing !== []) {
            return $existing[0];
        }

        return $this->app->databasePath('migrations/'.date('Y_m_d_His').'_create_laravel_refresh_tokens_table.php');
    }
}
