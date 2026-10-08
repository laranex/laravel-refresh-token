<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Laranex\RefreshToken\RefreshTokenServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * One RSA key pair shared by the whole run; generating one per test is slow.
     *
     * @var array{private: string, public: string}|null
     */
    protected static ?array $keyPair = null;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function tearDown(): void
    {
        RefreshToken::useRefreshTokenModel(RefreshTokenModel::class);
        RefreshToken::$refreshTokensExpireIn = null;
        RefreshToken::$keyPath = null;

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            RefreshTokenServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $keys = static::keyPair();

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('refresh-token.private_key', $keys['private']);
        $app['config']->set('refresh-token.public_key', $keys['public']);
    }

    /**
     * @return array{private: string, public: string}
     */
    public static function keyPair(): array
    {
        return static::$keyPair ??= static::generateKeyPair();
    }

    /**
     * @return array{private: string, public: string}
     */
    public static function generateKeyPair(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $details = $key === false ? false : openssl_pkey_get_details($key);

        if ($key === false || $details === false || ! openssl_pkey_export($key, $private)) {
            throw new RuntimeException('Unable to generate an RSA key pair for the test suite.');
        }

        return ['private' => $private, 'public' => $details['key']];
    }

    /**
     * A temporary, empty directory for key files.
     */
    protected function temporaryKeyDirectory(): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-refresh-token-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);

        $this->beforeApplicationDestroyed(function () use ($directory): void {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($directory);
        });

        return $directory;
    }
}
