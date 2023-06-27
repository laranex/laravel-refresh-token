<?php

namespace Laranex\RefreshToken;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Config\Repository as Config;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint;
use League\OAuth2\Server\CryptKey;
use Throwable;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;

class RefreshToken
{
    /**
     * The refresh token model class name.
     */
    public static string $refreshTokenModel = RefreshTokenModel::class;

    /**
     * The date when refresh tokens expire.
     *
     * @var ?DateInterval
     */
    public static ?DateInterval $refreshTokensExpireIn;

    /**
     * The storage location of the encryption keys.
     */
    public static string $keyPath;

    /**
     * Set the refresh token model class name.
     */
    public static function useRefreshTokenModel(string $refreshTokenModel): void
    {
        static::$refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     */
    public static function refreshTokenModel(): string
    {
        return static::$refreshTokenModel;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * @param  ?DateTimeInterface $date
     */
    public static function refreshTokensExpireIn(DateTimeInterface $date = null): DateInterval|static
    {
        if (is_null($date)) {
            return static::$refreshTokensExpireIn ?? new DateInterval('P1Y');
        }

        static::$refreshTokensExpireIn = Carbon::now()->diff($date);

        return new static;
    }

    /**
     * Get the refresh token instance for the given JWT.
     */
    public static function tokenable(string $jwtToken): ?RefreshTokenModel
    {
        try {
            $verifiedToken = (new JwtFacade())->parse(
                $jwtToken,
                new Constraint\SignedWith(new Sha256(), InMemory::plainText(self::makeCryptKey('public')->getKeyContents())),
                new Constraint\StrictValidAt(
                    new SystemClock(new DateTimeZone(date_default_timezone_get()))
                ),
            );

            return static::refreshTokenModel()::where('revoked', false)
                ->find($verifiedToken->claims()->get('jti'));
        } catch (Throwable $_) {
            return null;
        }
    }

    /**
     * Set the storage location of the encryption keys.
     */
    public static function loadKeysFrom(string $path): void
    {
        static::$keyPath = $path;
    }

    /**
     * The location of the encryption keys.
     */
    public static function keyPath(string $file): string
    {
        $file = ltrim($file, '/\\');

        return static::$keyPath
            ? rtrim(static::$keyPath, '/\\') . DIRECTORY_SEPARATOR . $file
            : storage_path($file);
    }

    /**
     * Create a CryptKey instance without permissions check.
     */
    public static function makeCryptKey(string $type): CryptKey
    {
        $key = str_replace('\\n', '\n', app()->make(Config::class)->get('refresh-token.' . $type . '_key') ?? '');

        if (!$key) {
            $key = 'file://' . RefreshToken::keyPath('oauth-' . $type . '.key');
        }

        return new CryptKey($key, null, false);
    }
}
