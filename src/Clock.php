<?php

declare(strict_types=1);

namespace Laranex\RefreshToken;

use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Psr\Clock\ClockInterface;

/**
 * A PSR-20 clock backed by Laravel's Carbon, so token timestamps honour
 * `Carbon::setTestNow()` and the `travel()` test helpers.
 */
final class Clock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return Carbon::now()->toImmutable();
    }
}
