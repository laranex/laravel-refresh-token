<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Exceptions;

use RuntimeException;

class MissingKeyException extends RuntimeException
{
    public static function forFile(string $type, string $path): self
    {
        return new self(sprintf(
            'The refresh token %s key was not found or is empty at [%s]. Run `php artisan refresh-token:keys` or set the REFRESH_TOKEN_%s_KEY environment variable.',
            $type,
            $path,
            strtoupper($type),
        ));
    }
}
