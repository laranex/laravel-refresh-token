<?php

declare(strict_types=1);

use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Workbench\Database\Factories\UserFactory;

it('deletes expired and revoked tokens and keeps the valid ones', function () {
    RefreshTokenModel::factory()->count(4)->create();
    RefreshTokenModel::factory()->count(3)->expired()->create();
    RefreshTokenModel::factory()->count(2)->revoked()->create();

    $this->artisan('refresh-token:prune')
        ->expectsOutput('Pruned 5 refresh token(s).')
        ->assertExitCode(0);

    expect(RefreshTokenModel::query()->count())->toBe(4)
        ->and(RefreshTokenModel::query()->where('revoked', true)->count())->toBe(0)
        ->and(RefreshTokenModel::query()->where('expires_at', '<', now())->count())->toBe(0);
});

it('keeps tokens that are still valid for the trait user', function () {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();
    $user->createRefreshToken();
    RefreshToken::tokenable($user->createRefreshToken())->revoke();

    $this->artisan('refresh-token:prune')->assertExitCode(0);

    expect($user->refreshTokens()->count())->toBe(2)
        ->and(RefreshToken::tokenable($jwt))->toBeInstanceOf(RefreshTokenModel::class);
});

it('prunes nothing when every token is valid', function () {
    RefreshTokenModel::factory()->count(2)->create();

    $this->artisan('refresh-token:prune')
        ->expectsOutput('Pruned 0 refresh token(s).')
        ->assertExitCode(0);

    expect(RefreshTokenModel::query()->count())->toBe(2);
});
