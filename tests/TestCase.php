<?php

namespace Laranex\RefreshToken\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laranex\RefreshToken\RefreshTokenServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Laranex\\RefreshToken\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            RefreshTokenServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-refresh-token_table.php.stub';
        $migration->up();
        */
    }
}
