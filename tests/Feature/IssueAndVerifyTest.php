<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Laranex\RefreshToken\Facades\RefreshToken as RefreshTokenFacade;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Laranex\RefreshToken\Tests\TestCase;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Workbench\Database\Factories\UserFactory;

it('issues a signed JWT and stores a matching refresh token row', function () {
    $user = UserFactory::new()->create();

    $jwt = $user->createRefreshToken();
    $claims = (new Parser(new JoseEncoder))->parse($jwt)->claims();

    expect($jwt)->toBeString()
        ->and(substr_count($jwt, '.'))->toBe(2)
        ->and($claims->get('sub'))->toBe((string) $user->getKey())
        ->and($claims->get('jti'))->toHaveLength(80)
        ->and(RefreshTokenModel::query()->count())->toBe(1);

    $stored = RefreshTokenModel::query()->firstOrFail();

    expect($stored->id)->toBe($claims->get('jti'))
        ->and($stored->refreshable_id)->toBe((string) $user->getKey())
        ->and($stored->refreshable_type)->toBe($user->getMorphClass())
        ->and($stored->revoked)->toBeFalse()
        ->and($stored->expires_at->timestamp)->toBe(Carbon::now()->addYear()->timestamp);
});

it('verifies a valid token and resolves the model it was issued for', function () {
    $user = UserFactory::new()->create();

    $verified = RefreshToken::tokenable($user->createRefreshToken());

    expect($verified)->toBeInstanceOf(RefreshTokenModel::class)
        ->and($verified->instance)->toBeInstanceOf($user::class)
        ->and($verified->instance->getKey())->toBe($user->getKey())
        ->and($user->refreshTokens()->count())->toBe(1)
        ->and($user->refreshTokens()->first()->is($verified))->toBeTrue();
});

it('verifies through the facade too', function () {
    $user = UserFactory::new()->create();

    expect(RefreshTokenFacade::tokenable($user->createRefreshToken()))->toBeInstanceOf(RefreshTokenModel::class)
        ->and(RefreshTokenFacade::refreshTokenModel())->toBe(RefreshTokenModel::class);
});

it('rejects garbage, tampered and foreign tokens', function (string $case) {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    $token = match ($case) {
        'empty' => '',
        'garbage' => 'not-a-jwt',
        'tampered payload' => (function () use ($jwt): string {
            [$header, $payload, $signature] = explode('.', $jwt);
            $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $claims['jti'] = bin2hex(random_bytes(40));

            return $header.'.'.rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=').'.'.$signature;
        })(),
        'tampered signature' => substr($jwt, 0, -4).'AAAA',
        'signed with another key' => (function () use ($user): string {
            $other = TestCase::generateKeyPair();
            config()->set('refresh-token.private_key', $other['private']);

            return $user->createRefreshToken();
        })(),
    };

    expect(RefreshToken::tokenable($token))->toBeNull();
})->with(['empty', 'garbage', 'tampered payload', 'tampered signature', 'signed with another key']);

it('rejects a token whose row was deleted', function () {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    RefreshTokenModel::query()->delete();

    expect(RefreshToken::tokenable($jwt))->toBeNull();
});

it('rejects an expired token', function () {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    $this->travel(1)->years();
    $this->travel(1)->seconds();

    expect(RefreshToken::tokenable($jwt))->toBeNull();
});

it('rejects a token that is not yet valid', function () {
    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    $this->travel(-1)->hours();

    expect(RefreshToken::tokenable($jwt))->toBeNull();
});

it('honours a custom expiry and keeps the DB row in sync', function () {
    RefreshToken::refreshTokensExpireIn(Carbon::now()->addDays(30));

    $user = UserFactory::new()->create();
    $jwt = $user->createRefreshToken();

    expect(RefreshTokenModel::query()->firstOrFail()->expires_at->timestamp)->toBe(Carbon::now()->addDays(30)->timestamp);

    $this->travel(29)->days();
    expect(RefreshToken::tokenable($jwt))->toBeInstanceOf(RefreshTokenModel::class);

    $this->travel(2)->days();
    expect(RefreshToken::tokenable($jwt))->toBeNull();
});

it('exposes the expiry interval and returns itself as a fluent setter', function () {
    Carbon::setTestNow(Carbon::now());

    expect(RefreshToken::refreshTokensExpireIn())->toEqual(new DateInterval('P1Y'));

    $result = RefreshToken::refreshTokensExpireIn(Carbon::now()->addHours(2));

    expect($result)->toBeInstanceOf(RefreshToken::class)
        ->and(RefreshToken::refreshTokensExpireIn()->h)->toBe(2)
        ->and(RefreshToken::refreshTokensExpireIn()->days)->toBe(0);
});

it('refuses to issue a token for a model that has not been saved', function () {
    UserFactory::new()->make()->createRefreshToken();
})->throws(LogicException::class, 'has been saved');
