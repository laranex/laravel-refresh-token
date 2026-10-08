<?php

declare(strict_types=1);

use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Workbench\Database\Factories\UserFactory;

it('revokes a single token so it no longer verifies', function () {
    $user = UserFactory::new()->create();
    $first = $user->createRefreshToken();
    $second = $user->createRefreshToken();

    $verified = RefreshToken::tokenable($first);

    expect($verified)->not->toBeNull()
        ->and($verified->revoke())->toBeTrue()
        ->and($verified->fresh()->revoked)->toBeTrue()
        ->and(RefreshToken::tokenable($first))->toBeNull()
        ->and(RefreshToken::tokenable($second))->toBeInstanceOf(RefreshTokenModel::class);
});

it('revokes every token of the same model and leaves other models alone', function () {
    $user = UserFactory::new()->create();
    $other = UserFactory::new()->create();

    $userTokens = [$user->createRefreshToken(), $user->createRefreshToken(), $user->createRefreshToken()];
    $otherToken = $other->createRefreshToken();

    $revokedCount = RefreshToken::tokenable($userTokens[1])->revokeAll();

    expect($revokedCount)->toBe(3)
        ->and($user->refreshTokens()->where('revoked', true)->count())->toBe(3)
        ->and(RefreshToken::tokenable($otherToken))->toBeInstanceOf(RefreshTokenModel::class);

    foreach ($userTokens as $jwt) {
        expect(RefreshToken::tokenable($jwt))->toBeNull();
    }
});

it('ships a factory with expired and revoked states', function () {
    RefreshTokenModel::factory()->create();
    RefreshTokenModel::factory()->expired()->create();
    RefreshTokenModel::factory()->revoked()->create();

    expect(RefreshTokenModel::query()->count())->toBe(3)
        ->and(RefreshTokenModel::query()->where('revoked', true)->count())->toBe(1)
        ->and(RefreshTokenModel::query()->where('expires_at', '<', now())->count())->toBe(1)
        ->and(RefreshTokenModel::query()->firstOrFail()->id)->toHaveLength(80);
});
