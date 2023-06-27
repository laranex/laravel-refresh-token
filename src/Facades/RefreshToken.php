<?php

namespace Laranex\RefreshToken\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laranex\RefreshToken\RefreshToken
 */
class RefreshToken extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Laranex\RefreshToken\RefreshToken::class;
    }
}
