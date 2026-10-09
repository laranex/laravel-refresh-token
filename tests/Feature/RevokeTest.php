<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Workbench\App\Models\User;
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

it('lets only one of two racing requests revoke the same token', function () {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    // Two requests verify the same token before either revokes it.
    $first = RefreshToken::tokenable($jwt);
    $second = RefreshToken::tokenable($jwt);

    expect($first->revoke())->toBeTrue()
        ->and($second->revoke())->toBeFalse()
        ->and($second->revoked)->toBeTrue()
        ->and($first->revoke())->toBeFalse()
        ->and($first->fresh()->revoked)->toBeTrue();
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

it('defaults the factory to the configured user model morph class', function () {
    config()->set('auth.providers.users.model', User::class);

    expect(RefreshTokenModel::factory()->make()->refreshable_type)->toBe(User::class);

    Relation::morphMap(['user' => User::class]);

    try {
        expect(RefreshTokenModel::factory()->make()->refreshable_type)->toBe('user');
    } finally {
        Relation::morphMap([], false);
    }

    config()->set('auth.providers.users.model', null);

    expect(RefreshTokenModel::factory()->make()->refreshable_type)->toBe('App\Models\User');
});

it('attaches factory tokens to a specific model through the instance relation', function () {
    $user = UserFactory::new()->create();

    $token = RefreshTokenModel::factory()->for($user, 'instance')->create();

    expect($token->refreshable_type)->toBe($user->getMorphClass())
        ->and((string) $token->refreshable_id)->toBe((string) $user->getKey())
        ->and($token->instance?->is($user))->toBeTrue();
});
