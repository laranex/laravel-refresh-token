<?php

namespace Laranex\RefreshToken;

use Laranex\RefreshToken\Commands\KeysCommand;
use Laranex\RefreshToken\Commands\PruneCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RefreshTokenServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-refresh-token')
            ->hasConfigFile()
            ->hasCommands([KeysCommand::class, PruneCommand::class])
            ->hasMigration('create_laravel_refresh_tokens_table')
            ->runsMigrations();

        RefreshToken::loadKeysFrom(storage_path());
    }
}

