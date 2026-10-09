<?php

declare(strict_types=1);

namespace Laranex\RefreshToken;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laranex\RefreshToken\Exceptions\MissingKeyException;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class RefreshToken
{
    /**
     * Final so that `new static` in the fluent setters is always safe.
     */
    final public function __construct()
    {
        //
    }

    /**
     * The refresh token model class name.
     *
     * @var class-string<RefreshTokenModel>
     */
    public static string $refreshTokenModel = RefreshTokenModel::class;

    /**
     * How long a freshly issued refresh token stays valid. Defaults to one year.
     */
    public static ?DateInterval $refreshTokensExpireIn = null;

    /**
     * The storage location of the encryption keys. Defaults to the application's storage path.
     */
    public static ?string $keyPath = null;

    /**
     * Set the refresh token model class name.
     *
     * @param  class-string<RefreshTokenModel>  $refreshTokenModel
     */
    public static function useRefreshTokenModel(string $refreshTokenModel): void
    {
        static::$refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     *
     * @return class-string<RefreshTokenModel>
     */
    public static function refreshTokenModel(): string
    {
        return static::$refreshTokenModel;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * Pass a date to make every token issued from now on expire after the same
     * amount of time that separates that date from the current moment.
     *
     * @return ($date is null ? DateInterval : static)
     *
     * @throws InvalidArgumentException when the date is not in the future
     */
    public static function refreshTokensExpireIn(?DateTimeInterface $date = null): DateInterval|static
    {
        if ($date === null) {
            return static::$refreshTokensExpireIn ?? new DateInterval('P1Y');
        }

        $now = DateTimeImmutable::createFromInterface((new Clock)->now());

        if ($date <= $now) {
            throw new InvalidArgumentException(sprintf(
                'Refresh tokens must expire in the future, but [%s] is not after the current time [%s]. Pass a future date, for example now()->addDays(30).',
                $date->format(DateTimeInterface::ATOM),
                $now->format(DateTimeInterface::ATOM),
            ));
        }

        // Diff as plain PHP dates: some Carbon 3 releases return an interval whose `days` is false.
        static::$refreshTokensExpireIn = $now->diff($date);

        return new static;
    }

    /**
     * Get the refresh token instance for the given JWT, or null when it is invalid, expired or revoked.
     */
    public static function tokenable(string $jwtToken): ?RefreshTokenModel
    {
        if ($jwtToken === '') {
            return null;
        }

        try {
            $verifiedToken = (new JwtFacade)->parse(
                $jwtToken,
                new SignedWith(new Sha256, InMemory::plainText(static::keyContents('public'))),
                new StrictValidAt(new Clock),
            );
        } catch (JwtException) {
            return null;
        }

        $tokenId = $verifiedToken->claims()->get('jti');

        if (! is_string($tokenId) || $tokenId === '') {
            return null;
        }

        return static::refreshTokenModel()::query()
            ->whereKey($tokenId)
            ->where('revoked', false)
            ->where('expires_at', '>', (new Clock)->now())
            ->first();
    }

    /**
     * Set the storage location of the encryption keys.
     */
    public static function loadKeysFrom(string $path): void
    {
        static::$keyPath = $path;
    }

    /**
     * The location of the given encryption key file.
     */
    public static function keyPath(string $file): string
    {
        $file = ltrim($file, '/\\');

        return static::$keyPath !== null && static::$keyPath !== ''
            ? rtrim(static::$keyPath, '/\\').DIRECTORY_SEPARATOR.$file
            : App::storagePath($file);
    }

    /**
     * Get the PEM contents of the "public" or "private" key.
     *
     * The key is read from the `refresh-token.<type>_key` config value when it is
     * set, otherwise from the `refresh-token-<type>.key` file in the key path.
     *
     * @return non-empty-string
     *
     * @throws MissingKeyException
     */
    public static function keyContents(string $type): string
    {
        $configured = Config::get('refresh-token.'.$type.'_key');

        $configured = is_string($configured) ? str_replace('\\n', "\n", $configured) : '';

        if ($configured !== '' && trim($configured) !== '') {
            return $configured;
        }

        $path = static::keyPath('refresh-token-'.$type.'.key');

        $contents = is_file($path) ? (string) file_get_contents($path) : '';

        if ($contents !== '' && trim($contents) !== '') {
            return $contents;
        }

        throw MissingKeyException::forFile($type, $path);
    }
}
