<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Laranex\RefreshToken\Clock;
use Laranex\RefreshToken\Exceptions\MissingKeyException;

it('follows the frozen Carbon time so tokens can be tested with travel()', function () {
    Carbon::setTestNow('2020-01-02 03:04:05');

    $now = (new Clock)->now();

    expect($now)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($now->format('Y-m-d H:i:s'))->toBe('2020-01-02 03:04:05');

    Carbon::setTestNow();

    expect((new Clock)->now()->getTimestamp())->toBeGreaterThan($now->getTimestamp());
});

it('explains where the key was expected and how to create it', function () {
    $exception = MissingKeyException::forFile('private', '/srv/app/storage/refresh-token-private.key');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toContain('/srv/app/storage/refresh-token-private.key')
        ->and($exception->getMessage())->toContain('php artisan refresh-token:keys')
        ->and($exception->getMessage())->toContain('REFRESH_TOKEN_PRIVATE_KEY');
});
