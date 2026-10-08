<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laranex\RefreshToken\Console\Commands\KeysCommand;
use Laranex\RefreshToken\Console\Commands\PruneCommand;
use Laranex\RefreshToken\Facades\RefreshToken as RefreshTokenFacade;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Laranex\RefreshToken\RefreshTokenServiceProvider;
use Workbench\Database\Factories\UserFactory;

it('merges the default configuration', function () {
    expect(config('refresh-token.table'))->toBe('laravel_refresh_tokens')
        ->and(config('refresh-token'))->toHaveKeys(['private_key', 'public_key', 'table']);
});

it('registers the console commands', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKeys(['refresh-token:keys', 'refresh-token:prune'])
        ->and($commands['refresh-token:keys'])->toBeInstanceOf(KeysCommand::class)
        ->and($commands['refresh-token:prune'])->toBeInstanceOf(PruneCommand::class);
});

it('runs the package migration without publishing it', function () {
    expect(Schema::hasTable('laravel_refresh_tokens'))->toBeTrue()
        ->and(Schema::hasColumns('laravel_refresh_tokens', ['id', 'refreshable_id', 'refreshable_type', 'revoked', 'expires_at', 'created_at', 'updated_at']))->toBeTrue();
});

it('publishes the config and the migration under their tags', function () {
    $config = realpath(__DIR__.'/../../config/refresh-token.php');
    $migration = realpath(__DIR__.'/../../database/migrations/create_laravel_refresh_tokens_table.php');

    $configPaths = ServiceProvider::pathsToPublish(RefreshTokenServiceProvider::class, 'refresh-token-config');
    $migrationPaths = ServiceProvider::pathsToPublish(RefreshTokenServiceProvider::class, 'refresh-token-migrations');
    $allPaths = ServiceProvider::pathsToPublish(RefreshTokenServiceProvider::class, 'refresh-token');

    $normalise = fn (array $paths): array => array_map(fn (string $path): string => (string) realpath($path), array_keys($paths));

    expect($normalise($configPaths))->toBe([$config])
        ->and(array_values($configPaths))->toBe([config_path('refresh-token.php')])
        ->and($normalise($migrationPaths))->toBe([$migration])
        ->and(array_values($migrationPaths)[0])->toMatch('/\d{4}_\d{2}_\d{2}_\d{6}_create_laravel_refresh_tokens_table\.php$/')
        ->and(array_values($migrationPaths)[0])->toStartWith(database_path('migrations'))
        ->and($normalise($allPaths))->toBe([$config, $migration]);
});

it('resolves the facade to the package class', function () {
    expect(RefreshTokenFacade::getFacadeRoot())->toBeInstanceOf(RefreshToken::class)
        ->and(RefreshTokenFacade::keyPath('x.key'))->toBe(RefreshToken::keyPath('x.key'));
});

it('reads the table name from the configuration', function () {
    config()->set('refresh-token.table', 'custom_refresh_tokens');

    expect((new RefreshTokenModel)->getTable())->toBe('custom_refresh_tokens');

    config()->set('refresh-token.table', null);

    expect((new RefreshTokenModel)->getTable())->toBe('laravel_refresh_tokens');
});

it('issues and verifies through a custom model class', function () {
    $custom = new class extends RefreshTokenModel
    {
        public static int $lookups = 0;

        public function getTable(): string
        {
            self::$lookups++;

            return parent::getTable();
        }
    };

    RefreshToken::useRefreshTokenModel($custom::class);

    $user = UserFactory::new()->create();
    $verified = RefreshToken::tokenable($user->createRefreshToken());

    expect(RefreshToken::refreshTokenModel())->toBe($custom::class)
        ->and($verified)->toBeInstanceOf($custom::class)
        ->and($user->refreshTokens()->getRelated())->toBeInstanceOf($custom::class)
        ->and($custom::$lookups)->toBeGreaterThan(0);
});
