<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Facades;

use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Facade;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;

/**
 * @method static void useRefreshTokenModel(class-string<RefreshTokenModel> $refreshTokenModel)
 * @method static class-string<RefreshTokenModel> refreshTokenModel()
 * @method static DateInterval|\Laranex\RefreshToken\RefreshToken refreshTokensExpireIn(DateTimeInterface|null $date = null)
 * @method static RefreshTokenModel|null tokenable(string $jwtToken)
 * @method static void loadKeysFrom(string $path)
 * @method static string keyPath(string $file)
 * @method static string keyContents(string $type)
 *
 * @see \Laranex\RefreshToken\RefreshToken
 */
class RefreshToken extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Laranex\RefreshToken\RefreshToken::class;
    }
}
