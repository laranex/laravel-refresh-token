<?php

declare(strict_types=1);

use Laranex\RefreshToken\Exceptions\MissingKeyException;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Laranex\RefreshToken\Tests\TestCase;
use Workbench\Database\Factories\UserFactory;

beforeEach(function () {
    // Make the command and the verifier read the key files instead of the inline test keys.
    config()->set('refresh-token.private_key', null);
    config()->set('refresh-token.public_key', null);

    $this->keyDirectory = $this->temporaryKeyDirectory();
    RefreshToken::loadKeysFrom($this->keyDirectory);
});

it('writes a key pair the issuer and verifier both read', function () {
    $this->artisan('refresh-token:keys', ['--length' => 2048])
        ->expectsOutput('Encryption keys generated successfully.')
        ->assertExitCode(0);

    $publicKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-public.key';
    $privateKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-private.key';

    expect($publicKey)->toBeFile()
        ->and($privateKey)->toBeFile()
        ->and(file_get_contents($publicKey))->toStartWith('-----BEGIN PUBLIC KEY-----')
        ->and(file_get_contents($privateKey))->toStartWith('-----BEGIN PRIVATE KEY-----')
        ->and(RefreshToken::keyContents('public'))->toBe(file_get_contents($publicKey))
        ->and(RefreshToken::keyContents('private'))->toBe(file_get_contents($privateKey));

    $user = UserFactory::new()->create();

    expect(RefreshToken::tokenable($user->createRefreshToken()))->toBeInstanceOf(RefreshTokenModel::class);
});

it('writes the private key readable by its owner only', function () {
    $privateKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-private.key';
    file_put_contents($privateKey, 'old');
    chmod($privateKey, 0644);

    $this->artisan('refresh-token:keys', ['--length' => 2048, '--force' => true])->assertExitCode(0);

    clearstatcache();

    expect(fileperms($privateKey) & 0777)->toBe(0600)
        ->and(file_get_contents($privateKey))->toStartWith('-----BEGIN PRIVATE KEY-----');
})->skipOnWindows();

it('refuses to overwrite existing keys unless forced', function () {
    $this->artisan('refresh-token:keys', ['--length' => 2048])->assertExitCode(0);

    $privateKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-private.key';
    $original = file_get_contents($privateKey);

    $this->artisan('refresh-token:keys', ['--length' => 2048])
        ->expectsOutput('Encryption keys already exist. Use the --force option to overwrite them.')
        ->assertExitCode(1);

    expect(file_get_contents($privateKey))->toBe($original);

    $this->artisan('refresh-token:keys', ['--length' => 2048, '--force' => true])->assertExitCode(0);

    expect(file_get_contents($privateKey))->not->toBe($original);
});

it('rejects key lengths below 2048 bits', function () {
    $this->artisan('refresh-token:keys', ['--length' => 1024])
        ->expectsOutput('The key length must be at least 2048 bits.')
        ->assertExitCode(1);

    expect(glob($this->keyDirectory.DIRECTORY_SEPARATOR.'*'))->toBe([]);
});

it('creates a missing key directory', function () {
    $directory = $this->keyDirectory.DIRECTORY_SEPARATOR.'nested'.DIRECTORY_SEPARATOR.'keys';
    RefreshToken::loadKeysFrom($directory);

    $this->artisan('refresh-token:keys', ['--length' => 2048])->assertExitCode(0);

    expect($directory.DIRECTORY_SEPARATOR.'refresh-token-private.key')->toBeFile()
        ->and($directory.DIRECTORY_SEPARATOR.'refresh-token-public.key')->toBeFile();

    @unlink($directory.DIRECTORY_SEPARATOR.'refresh-token-private.key');
    @unlink($directory.DIRECTORY_SEPARATOR.'refresh-token-public.key');
    @rmdir($directory);
    @rmdir(dirname($directory));
});

it('fails when the key directory cannot be created', function () {
    $file = $this->keyDirectory.DIRECTORY_SEPARATOR.'not-a-directory';
    file_put_contents($file, '');
    RefreshToken::loadKeysFrom($file.DIRECTORY_SEPARATOR.'keys');

    $this->artisan('refresh-token:keys', ['--length' => 2048])
        ->expectsOutput('Unable to create the key directory ['.$file.DIRECTORY_SEPARATOR.'keys].')
        ->assertExitCode(1);
});

it('fails instead of reporting success when a key cannot be written', function () {
    $privateKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-private.key';
    mkdir($privateKey);

    $this->artisan('refresh-token:keys', ['--length' => 2048, '--force' => true])
        ->expectsOutput('Unable to write the private key to ['.$privateKey.'].')
        ->doesntExpectOutput('Encryption keys generated successfully.')
        ->assertExitCode(1);

    expect($this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-public.key')->not->toBeFile();

    rmdir($privateKey);
});

it('fails and removes the private key when the public key cannot be written', function () {
    $publicKey = $this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-public.key';
    mkdir($publicKey);

    $this->artisan('refresh-token:keys', ['--length' => 2048, '--force' => true])
        ->expectsOutput('Unable to write the public key to ['.$publicKey.'].')
        ->assertExitCode(1);

    expect($this->keyDirectory.DIRECTORY_SEPARATOR.'refresh-token-private.key')->not->toBeFile();

    rmdir($publicKey);
});

it('stores keys in the storage path by default', function () {
    RefreshToken::$keyPath = null;

    expect(RefreshToken::keyPath('refresh-token-public.key'))->toBe(storage_path('refresh-token-public.key'))
        ->and(RefreshToken::keyPath('/refresh-token-public.key'))->toBe(storage_path('refresh-token-public.key'));

    RefreshToken::loadKeysFrom('/custom/keys/');

    expect(RefreshToken::keyPath('refresh-token-public.key'))->toBe('/custom/keys'.DIRECTORY_SEPARATOR.'refresh-token-public.key');
});

it('tells the developer how to fix a missing key', function () {
    RefreshToken::keyContents('private');
})->throws(MissingKeyException::class, 'php artisan refresh-token:keys');

it('verifying without a public key fails loudly instead of returning null', function () {
    RefreshToken::tokenable('a.b.c');
})->throws(MissingKeyException::class, 'REFRESH_TOKEN_PUBLIC_KEY');

it('prefers inline keys from the config and expands escaped newlines', function () {
    $keys = TestCase::generateKeyPair();

    config()->set('refresh-token.private_key', str_replace("\n", '\n', $keys['private']));
    config()->set('refresh-token.public_key', str_replace("\n", '\n', $keys['public']));

    expect(RefreshToken::keyContents('private'))->toBe($keys['private'])
        ->and(RefreshToken::keyContents('public'))->toBe($keys['public']);

    $user = UserFactory::new()->create();

    expect(RefreshToken::tokenable($user->createRefreshToken()))->toBeInstanceOf(RefreshTokenModel::class);
});
