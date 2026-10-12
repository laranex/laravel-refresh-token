<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
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

it('deletes in chunks of the given size and counts a token that is both expired and revoked once', function () {
    RefreshTokenModel::factory()->count(2)->create();
    RefreshTokenModel::factory()->count(3)->expired()->create();
    RefreshTokenModel::factory()->count(2)->revoked()->create();
    RefreshTokenModel::factory()->expired()->revoked()->create();

    DB::enableQueryLog();

    $this->artisan('refresh-token:prune', ['--chunk' => 2])
        ->expectsOutput('Pruned 6 refresh token(s).')
        ->assertExitCode(0);

    $deletes = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with(strtolower($query['query']), 'delete'));

    expect(RefreshTokenModel::query()->count())->toBe(2)
        ->and($deletes)->toHaveCount(4)
        ->and($deletes->every(fn (array $query): bool => count($query['bindings']) <= 2))->toBeTrue();
});

it('rejects a chunk size that is not a positive integer', function (string $chunk) {
    RefreshTokenModel::factory()->revoked()->create();

    $this->artisan('refresh-token:prune', ['--chunk' => $chunk])
        ->expectsOutput('The --chunk option must be a positive integer.')
        ->assertExitCode(2);

    expect(RefreshTokenModel::query()->count())->toBe(1);
})->with(['0', '-5', 'many']);

it('indexes the columns the prune command filters on', function () {
    $indexed = collect(DB::select("select name from pragma_index_list('laravel_refresh_tokens')"))
        ->flatMap(fn (object $index): array => array_column(
            DB::select('select name from pragma_index_info(?)', [$index->name]),
            'name',
        ));

    expect($indexed)->toContain('expires_at', 'revoked');
});
